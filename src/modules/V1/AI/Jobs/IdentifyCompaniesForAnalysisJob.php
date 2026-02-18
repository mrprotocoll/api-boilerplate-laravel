<?php

namespace Modules\V1\AI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\V1\Company\Models\Company;

class IdentifyCompaniesForAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    protected string $analysisType;
    protected int $batchSize;

    public function __construct(string $analysisType = 'emergency_concern', int $batchSize = 1000)
    {
        $this->analysisType = $analysisType;
        $this->batchSize = $batchSize;
    }

    public function handle(): void
    {
        Log::info("Starting company identification for analysis", [
            'analysis_type' => $this->analysisType,
            'batch_size' => $this->batchSize,
        ]);

        $companies = $this->getCompaniesDueForAnalysis();

        Log::info("Found companies needing analysis", [
            'count' => $companies->count(),
        ]);

        $prioritized = $this->prioritizeCompanies($companies);

        foreach ($prioritized as $priority => $companyGroup) {
            foreach ($companyGroup as $company) {
                AnalyzeCompanyConcernJob::dispatch(
                    $company->id,
                    $this->analysisType
                )->onQueue($this->getQueueForPriority($priority));
            }

            Log::info("Dispatched analysis jobs", [
                'priority' => $priority,
                'count' => count($companyGroup),
            ]);
        }
    }

    /**
     * Get companies that are due for analysis
     */
    protected function getCompaniesDueForAnalysis()
    {
        return Company::select('companies.*')
            ->leftJoin('company_concern_analyses as cca', function ($join) {
                $join->on('companies.id', '=', 'cca.company_id')
                    ->where('cca.analysis_type', '=', $this->analysisType)
                    ->whereIn('cca.status', ['completed', 'failed']);
            })
            ->leftJoin('company_concern_analyses as latest', function ($join) {
                $join->on('companies.id', '=', 'latest.company_id')
                    ->where('latest.analysis_type', '=', $this->analysisType)
                    ->whereRaw('latest.id = (
                        SELECT MAX(id)
                        FROM company_concern_analyses
                        WHERE company_id = companies.id
                        AND analysis_type = ?
                    )', [$this->analysisType]);
            })
            ->where(function ($query) {
                $query->whereNull('latest.id') // Never analyzed
                ->orWhere('latest.next_analysis_due', '<=', now()) // Due for analysis
                ->orWhere(function ($q) {
                    // Failed and not retried recently
                    $q->where('latest.status', 'failed')
                        ->where('latest.retry_count', '<', 3)
                        ->where('latest.updated_at', '<=', now()->subHours(6));
                });
            })
            // Must have reviews
            ->where(function ($query) {
                $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('reviews')
                        ->whereColumn('reviews.company_id', 'companies.id')
                        ->where('reviews.status', 'submitted');
                })
                    ->orWhereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('interview_reviews')
                            ->whereColumn('interview_reviews.company_id', 'companies.id')
                            ->where('interview_reviews.status', 'submitted');
                    });
            })
            // Exclude companies currently being processed
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('company_concern_analyses')
                    ->whereColumn('company_concern_analyses.company_id', 'companies.id')
                    ->where('company_concern_analyses.analysis_type', $this->analysisType)
                    ->where('company_concern_analyses.status', 'processing');
            })
            ->groupBy('companies.id')
            ->limit($this->batchSize)
            ->get();
    }

    /**
     * Prioritize companies based on review volume
     */
    protected function prioritizeCompanies($companies): array
    {
        $prioritized = [
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($companies as $company) {
            // Get review count in last 30 days
            $recentReviewCount = $this->getRecentReviewCount($company->id);

            if ($recentReviewCount >= 50) {
                $prioritized['high'][] = $company;
            } elseif ($recentReviewCount >= 20) {
                $prioritized['medium'][] = $company;
            } else {
                $prioritized['low'][] = $company;
            }
        }

        return $prioritized;
    }

    /**
     * Get recent review count for a company
     */
    protected function getRecentReviewCount(int $companyId): int
    {
        $thirtyDaysAgo = now()->subDays(30)->timestamp;

        $reviewCount = DB::table('reviews')
            ->where('company_id', $companyId)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $interviewReviewCount = DB::table('interview_reviews')
            ->where('company_id', $companyId)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        return $reviewCount + $interviewReviewCount;
    }

    /**
     * Get queue name based on priority
     */
    protected function getQueueForPriority(string $priority): string
    {
        return match ($priority) {
            'high' => 'analysis-high',
            'medium' => 'analysis-medium',
            'low' => 'analysis-low',
            default => 'default',
        };
    }
}
