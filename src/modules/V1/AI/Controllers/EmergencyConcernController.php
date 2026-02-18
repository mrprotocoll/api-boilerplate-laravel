<?php

namespace Modules\V1\AI\Controllers;


use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\V1\Company\Models\CompanyAnalysis;

class EmergencyConcernController extends Controller
{
    /**
     * Get all emergency concern signals from database
     */
    public function index(Request $request): JsonResponse
    {
        $query = CompanyAnalysis::with('company')
            ->emergencyConcerns()
            ->completed()
            ->where('severity', '!=', 'none')
            ->orderBy('severity', 'desc')
            ->orderBy('analyzed_at', 'desc');

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('analyzed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('analyzed_at', '<=', $request->to_date);
        }

        // Search by company name
        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'ILIKE', "%{$request->search}%");
            });
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);
        $concerns = $query->paginate($perPage);

        $data = $concerns->map(function ($analysis) {
            return $this->formatConcernResponse($analysis);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $concerns->currentPage(),
                'per_page' => $concerns->perPage(),
                'total' => $concerns->total(),
                'last_page' => $concerns->lastPage(),
            ],
        ]);
    }

    /**
     * Get concern signal for specific company
     */
    public function show(Request $request, int $companyId): JsonResponse
    {
        $analysis = CompanyAnalysis::with('company')
            ->emergencyConcerns()
            ->completed()
            ->where('company_id', $companyId)
            ->orderBy('analyzed_at', 'desc')
            ->first();

        if (!$analysis) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No emergency concern analysis found for this company',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatDetailedConcernResponse($analysis),
        ]);
    }

    /**
     * Get analysis history for a company
     */
    public function getHistory(Request $request, int $companyId): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);

        $history = CompanyAnalysis::with('company')
            ->emergencyConcerns()
            ->completed()
            ->where('company_id', $companyId)
            ->orderBy('analyzed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($analysis) {
                return $this->formatConcernResponse($analysis);
            });

        return response()->json([
            'success' => true,
            'data' => $history,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Get concerns filtered by severity
     */
    public function getBySeverity(Request $request, string $severity): JsonResponse
    {
        if (!in_array($severity, ['critical', 'high', 'medium', 'low'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid severity level',
            ], 400);
        }

        $concerns = CompanyAnalysis::with('company')
            ->emergencyConcerns()
            ->completed()
            ->withSeverity($severity)
            ->orderBy('analyzed_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($analysis) {
                return $this->formatConcernResponse($analysis);
            });

        return response()->json([
            'success' => true,
            'data' => $concerns,
            'severity' => $severity,
            'total' => $concerns->count(),
        ]);
    }

    /**
     * Get statistics about concerns
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $stats = [
            'total_analyzed' => CompanyAnalysis::emergencyConcerns()->completed()->count(),
            'with_concerns' => CompanyAnalysis::emergencyConcerns()->completed()
                ->where('severity', '!=', 'none')->count(),
            'by_severity' => [
                'critical' => CompanyAnalysis::emergencyConcerns()->completed()->withSeverity('critical')->count(),
                'high' => CompanyAnalysis::emergencyConcerns()->completed()->withSeverity('high')->count(),
                'medium' => CompanyAnalysis::emergencyConcerns()->completed()->withSeverity('medium')->count(),
                'low' => CompanyAnalysis::emergencyConcerns()->completed()->withSeverity('low')->count(),
            ],
            'analyzed_today' => CompanyAnalysis::emergencyConcerns()->completed()
                ->whereDate('analyzed_at', today())->count(),
            'analyzed_this_week' => CompanyAnalysis::emergencyConcerns()->completed()
                ->where('analyzed_at', '>=', now()->startOfWeek())->count(),
            'pending' => CompanyAnalysis::emergencyConcerns()->where('status', 'pending')->count(),
            'processing' => CompanyAnalysis::emergencyConcerns()->where('status', 'processing')->count(),
            'failed' => CompanyAnalysis::emergencyConcerns()->where('status', 'failed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Force analyze a specific company
     */
    public function analyzeCompany(Request $request, int $companyId): JsonResponse
    {
        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Check if already processing
        $existing = CompanyAnalysis::emergencyConcerns()
            ->where('company_id', $companyId)
            ->where('status', 'processing')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis already in progress for this company',
            ], 409);
        }

        // Dispatch analysis job
        AnalyzeCompanyConcernJob::dispatch($companyId, 'emergency_concern')
            ->onQueue('analysis-high');

        return response()->json([
            'success' => true,
            'message' => 'Analysis queued for company',
            'company_id' => $companyId,
            'company_name' => $company->name,
        ]);
    }

    /**
     * Trigger batch analysis for all companies
     */
    public function batchAnalyze(Request $request): JsonResponse
    {
        $batchSize = min((int) $request->get('batch_size', 1000), 5000);

        IdentifyCompaniesForAnalysisJob::dispatch('emergency_concern', $batchSize);

        return response()->json([
            'success' => true,
            'message' => 'Batch analysis job dispatched',
            'batch_size' => $batchSize,
        ]);
    }

    /**
     * Get recent analysis activity
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 7), 30);

        $activity = CompanyAnalysis::emergencyConcerns()
            ->completed()
            ->where('analyzed_at', '>=', now()->subDays($days))
            ->orderBy('analyzed_at', 'desc')
            ->limit(100)
            ->get()
            ->groupBy(function ($analysis) {
                return $analysis->analyzed_at->format('Y-m-d');
            })
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'total_analyzed' => $group->count(),
                    'with_concerns' => $group->where('severity', '!=', 'none')->count(),
                    'critical' => $group->where('severity', 'critical')->count(),
                    'high' => $group->where('severity', 'high')->count(),
                    'medium' => $group->where('severity', 'medium')->count(),
                    'low' => $group->where('severity', 'low')->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activity,
            'period_days' => $days,
        ]);
    }

    /**
     * Delete old analyses
     */
    public function cleanup(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 90), 365);

        $deleted = CompanyAnalysis::where('analyzed_at', '<', now()->subDays($days))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted analyses older than {$days} days",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Format concern response for API
     */
    protected function formatConcernResponse(CompanyAnalysis $analysis): array
    {
        $trend = $analysis->getTrend();

        return [
            'id' => $analysis->id,
            'company_id' => $analysis->company_id,
            'company_name' => $analysis->company->name,
            'severity' => $analysis->severity,
            'summary' => $analysis->summary,
            'concerns_count' => count($analysis->concerns ?? []),
            'reviews_analyzed' => $analysis->total_reviews_analyzed,
            'metrics' => $analysis->metrics,
            'analyzed_at' => $analysis->analyzed_at?->toISOString(),
            'next_analysis_due' => $analysis->next_analysis_due?->toISOString(),
            'trend' => $trend,
        ];
    }

    /**
     * Format detailed concern response
     */
    protected function formatDetailedConcernResponse(CompanyAnalysis $analysis): array
    {
        $basic = $this->formatConcernResponse($analysis);

        return array_merge($basic, [
            'concerns' => $analysis->concerns,
            'ai_insights' => $analysis->ai_insights,
            'status' => $analysis->status,
        ]);
    }
}
