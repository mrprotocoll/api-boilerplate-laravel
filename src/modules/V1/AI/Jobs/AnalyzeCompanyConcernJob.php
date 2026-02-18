<?php

namespace Modules\V1\AI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use Modules\V1\AI\Providers\AIFactory;
use Modules\V1\Company\Models\Company;
use Modules\V1\Company\Models\CompanyAnalysis;
use Modules\V1\Company\Models\Review;
use Modules\V1\Company\Models\InterviewReview;

class AnalyzeCompanyConcernJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120; // 2 minutes
    public int $tries = 3;
    public int $backoff = 60; // Wait 1 minute between retries

    protected int $companyId;
    protected string $analysisType;
    protected int $timeWindowDays = 30;

    public function __construct(int $companyId, string $analysisType = 'emergency_concern')
    {
        $this->companyId = $companyId;
        $this->analysisType = $analysisType;
    }

    public function handle(): void
    {
        $company = Company::find($this->companyId);

        if (!$company) {
            Log::warning("Company not found for analysis", ['company_id' => $this->companyId]);
            return;
        }

        // Create or get analysis record
        $analysis = CompanyAnalysis::firstOrCreate(
            [
                'company_id' => $this->companyId,
                'analysis_type' => $this->analysisType,
            ],
            [
                'status' => 'pending',
            ]
        );

        // Check if already processing
        if ($analysis->status === 'processing') {
            Log::info("Analysis already in progress", [
                'company_id' => $this->companyId,
                'analysis_id' => $analysis->id,
            ]);
            return;
        }

        $analysis->markAsProcessing();

        try {
            Log::info("Starting company analysis", [
                'company_id' => $this->companyId,
                'company_name' => $company->name,
                'analysis_type' => $this->analysisType,
            ]);

            // Perform analysis
            $result = $this->performAnalysis($company);

            // Store results
            $analysis->markAsCompleted([
                'severity' => $result['severity'],
                'concerns' => $result['concerns'],
                'metrics' => $result['metrics'],
                'ai_insights' => $result['ai_insights'],
                'summary' => $result['summary'],
                'reviews_analyzed' => $result['reviews_analyzed'],
                'interview_reviews_analyzed' => $result['interview_reviews_analyzed'],
                'total_reviews_analyzed' => $result['total_reviews_analyzed'],
            ]);

            Log::info("Company analysis completed", [
                'company_id' => $this->companyId,
                'severity' => $result['severity'],
                'concerns_count' => count($result['concerns']),
            ]);

        } catch (\Exception $e) {
            Log::error("Company analysis failed", [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $analysis->markAsFailed($e->getMessage());

            throw $e; // Re-throw for retry mechanism
        }
    }

    /**
     * Perform the actual analysis
     */
    protected function performAnalysis(Company $company): array
    {
        // Get reviews from last 30 days
        $recentReviews = $this->getRecentReviews($company, $this->timeWindowDays);
        $previousReviews = $this->getRecentReviews($company, 60, $this->timeWindowDays);

        if (empty($recentReviews)) {
            return [
                'severity' => 'none',
                'concerns' => [],
                'metrics' => [
                    'reviews_found' => 0,
                ],
                'ai_insights' => null,
                'summary' => 'No reviews available for analysis.',
                'reviews_analyzed' => 0,
                'interview_reviews_analyzed' => 0,
                'total_reviews_analyzed' => 0,
            ];
        }

        // Calculate metrics
        $metrics = $this->calculateMetrics($recentReviews, $previousReviews);

        // Perform AI analysis
        $aiInsights = $this->performAIAnalysis($company, $recentReviews, $metrics);

        // Determine severity
        $severity = $this->determineSeverity($metrics, $aiInsights);

        // Extract concerns
        $concerns = $this->extractConcerns($aiInsights, $metrics);

        // Generate summary
        $summary = $this->generateSummary($company, $metrics, $aiInsights, $severity);

        return [
            'severity' => $severity,
            'concerns' => $concerns,
            'metrics' => $metrics,
            'ai_insights' => $aiInsights,
            'summary' => $summary,
            'reviews_analyzed' => $metrics['review_count'],
            'interview_reviews_analyzed' => $metrics['interview_review_count'],
            'total_reviews_analyzed' => $metrics['total_reviews'],
        ];
    }

    /**
     * Get recent reviews for company
     */
    protected function getRecentReviews(Company $company, int $days, int $offsetDays = 0): array
    {
        $endDate = now()->subDays($offsetDays)->timestamp;
        $startDate = now()->subDays($days + $offsetDays)->timestamp;

        $reviews = [];

        // Company reviews
        $companyReviews = Review::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('ratings')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($companyReviews as $review) {
            $overallRating = $review->ratings->where('category', 'overall')->first();

            $reviews[] = [
                'type' => 'company',
                'id' => $review->id,
                'rating' => $overallRating?->rating ?? null,
                'pros' => $review->pros ?? '',
                'cons' => $review->cons ?? '',
                'suggestions' => $review->suggestions ?? '',
                'recommend_to_friend' => $review->recommend_to_friend ?? null,
                'ceo_approval' => $review->ceo_approval ?? null,
                'created_at' => $review->created_at,
            ];
        }

        // Interview reviews
        $interviewReviews = InterviewReview::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($interviewReviews as $review) {
            $reviews[] = [
                'type' => 'interview',
                'id' => $review->id,
                'experience_description' => $review->experience_description ?? '',
                'difficulty_level' => $review->difficulty_level ?? null,
                'offer_status' => $review->offer_status ?? null,
                'enjoyed_it' => $review->enjoyed_it ?? null,
                'created_at' => $review->created_at,
            ];
        }

        return $reviews;
    }

    /**
     * Calculate metrics from reviews
     */
    protected function calculateMetrics(array $recentReviews, array $previousReviews): array
    {
        // Count reviews by type
        $reviewCount = count(array_filter($recentReviews, fn($r) => $r['type'] === 'company'));
        $interviewReviewCount = count(array_filter($recentReviews, fn($r) => $r['type'] === 'interview'));

        // Calculate average ratings
        $recentRatings = array_filter(array_column($recentReviews, 'rating'));
        $currentAvgRating = !empty($recentRatings) ? array_sum($recentRatings) / count($recentRatings) : null;

        $previousRatings = array_filter(array_column($previousReviews, 'rating'));
        $previousAvgRating = !empty($previousRatings) ? array_sum($previousRatings) / count($previousRatings) : null;

        $ratingChange = ($currentAvgRating && $previousAvgRating)
            ? $previousAvgRating - $currentAvgRating
            : null;

        // Recommendation metrics
        $companyReviews = array_filter($recentReviews, fn($r) => $r['type'] === 'company');
        $recommendYes = count(array_filter($companyReviews, fn($r) => $r['recommend_to_friend'] === 'yes'));
        $recommendTotal = count(array_filter($companyReviews, fn($r) => $r['recommend_to_friend'] !== null));
        $recommendPercentage = $recommendTotal > 0 ? ($recommendYes / $recommendTotal) * 100 : null;

        // CEO approval
        $ceoApproveYes = count(array_filter($companyReviews, fn($r) => $r['ceo_approval'] === 'yes'));
        $ceoApproveTotal = count(array_filter($companyReviews, fn($r) => $r['ceo_approval'] !== null && $r['ceo_approval'] !== 'na'));
        $ceoApprovalPercentage = $ceoApproveTotal > 0 ? ($ceoApproveYes / $ceoApproveTotal) * 100 : null;

        // Interview metrics
        $interviewReviews = array_filter($recentReviews, fn($r) => $r['type'] === 'interview');
        $positiveExperience = count(array_filter($interviewReviews, fn($r) => $r['enjoyed_it'] === true));
        $totalInterviewWithFeedback = count(array_filter($interviewReviews, fn($r) => $r['enjoyed_it'] !== null));
        $positiveInterviewPercentage = $totalInterviewWithFeedback > 0
            ? ($positiveExperience / $totalInterviewWithFeedback) * 100
            : null;

        return [
            'review_count' => $reviewCount,
            'interview_review_count' => $interviewReviewCount,
            'total_reviews' => count($recentReviews),
            'current_avg_rating' => $currentAvgRating ? round($currentAvgRating, 2) : null,
            'previous_avg_rating' => $previousAvgRating ? round($previousAvgRating, 2) : null,
            'rating_change' => $ratingChange ? round($ratingChange, 2) : null,
            'recommend_percentage' => $recommendPercentage ? round($recommendPercentage, 1) : null,
            'ceo_approval_percentage' => $ceoApprovalPercentage ? round($ceoApprovalPercentage, 1) : null,
            'positive_interview_percentage' => $positiveInterviewPercentage ? round($positiveInterviewPercentage, 1) : null,
            'time_window_days' => $this->timeWindowDays,
        ];
    }

    /**
     * Perform AI analysis
     */
    protected function performAIAnalysis(Company $company, array $reviews, array $metrics): array
    {
        $ai = AIFactory::make();

        // Prepare review text
        $reviewTexts = [];
        foreach ($reviews as $review) {
            if ($review['type'] === 'company') {
                $text = "Review:\n";
                if (!empty($review['pros'])) $text .= "Pros: {$review['pros']}\n";
                if (!empty($review['cons'])) $text .= "Cons: {$review['cons']}\n";
                if (!empty($review['suggestions'])) $text .= "Suggestions: {$review['suggestions']}\n";
                $text .= "Rating: " . ($review['rating'] ?? 'N/A') . "\n";
                $reviewTexts[] = $text;
            } elseif ($review['type'] === 'interview') {
                $text = "Interview Experience:\n";
                if (!empty($review['experience_description'])) {
                    $text .= $review['experience_description'] . "\n";
                }
                $reviewTexts[] = $text;
            }
        }

        $combinedReviews = implode("\n---\n", $reviewTexts);

        // Build context
        $context = "Company: {$company->name}\n";
        $context .= "Analysis Period: Last {$metrics['time_window_days']} days\n";
        $context .= "Total Reviews: {$metrics['total_reviews']}\n";
        if ($metrics['current_avg_rating']) {
            $context .= "Current Avg Rating: {$metrics['current_avg_rating']}/5\n";
        }
        if ($metrics['rating_change']) {
            $context .= "Rating Change: " . ($metrics['rating_change'] > 0 ? '+' : '') . "{$metrics['rating_change']} stars\n";
        }

        $prompt = "{$context}\n\n"
            . "Analyze these employee reviews and interview experiences for {$company->name}. "
            . "Focus on identifying emergency concerns and critical issues that need immediate attention.\n\n"
            . "Reviews and Feedback:\n{$combinedReviews}\n\n"
            . "Provide a structured analysis including:\n"
            . "1. Key concerns (with severity and specific examples)\n"
            . "2. Patterns or trends you notice\n"
            . "3. Red flags that require immediate attention\n"
            . "4. Overall assessment and recommendations\n\n"
            . "Be specific, cite examples, and focus on actionable insights.";

        $response = $ai->structuredOutput($prompt, [
            'name' => 'emergency_concern_analysis',
            'type' => 'object',
            'properties' => [
                'key_concerns' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'severity' => ['type' => 'string', 'enum' => ['critical', 'high', 'medium', 'low']],
                            'examples' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'frequency' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'description', 'severity'],
                        'additionalProperties' => false
                    ]
                ],
                'patterns' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'red_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'overall_assessment' => ['type' => 'string'],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'required' => ['key_concerns', 'red_flags', 'overall_assessment'],
            'additionalProperties' => false
        ]);

        return $response->getStructured() ?? [];
    }

    /**
     * Determine overall severity
     */
    protected function determineSeverity(array $metrics, array $aiInsights): string
    {
        $severities = ['critical', 'high', 'medium', 'low', 'none'];
        $highestSeverity = 'none';

        // Check AI concerns
        if (!empty($aiInsights['key_concerns'])) {
            foreach ($aiInsights['key_concerns'] as $concern) {
                $concernSeverity = $concern['severity'] ?? 'low';
                if (array_search($concernSeverity, $severities) > array_search($highestSeverity, $severities)) {
                    $highestSeverity = $concernSeverity;
                }
            }
        }

        // Check metrics
        if ($metrics['rating_change'] && $metrics['rating_change'] >= 1.5) {
            if (array_search('critical', $severities) > array_search($highestSeverity, $severities)) {
                $highestSeverity = 'critical';
            }
        } elseif ($metrics['rating_change'] && $metrics['rating_change'] >= 1.0) {
            if (array_search('high', $severities) > array_search($highestSeverity, $severities)) {
                $highestSeverity = 'high';
            }
        }

        // Check recommendation percentage
        if ($metrics['recommend_percentage'] !== null && $metrics['recommend_percentage'] < 30) {
            if (array_search('high', $severities) > array_search($highestSeverity, $severities)) {
                $highestSeverity = 'high';
            }
        }

        return $highestSeverity;
    }

    /**
     * Extract structured concerns
     */
    protected function extractConcerns(array $aiInsights, array $metrics): array
    {
        $concerns = [];

        // Add AI-identified concerns
        if (!empty($aiInsights['key_concerns'])) {
            $concerns = $aiInsights['key_concerns'];
        }

        // Add metric-based concerns
        if ($metrics['rating_change'] && $metrics['rating_change'] >= 1.0) {
            $concerns[] = [
                'title' => 'Significant Rating Drop',
                'description' => "Company rating has dropped by {$metrics['rating_change']} stars in the last {$metrics['time_window_days']} days.",
                'severity' => $metrics['rating_change'] >= 1.5 ? 'critical' : 'high',
                'type' => 'metric',
            ];
        }

        if ($metrics['recommend_percentage'] !== null && $metrics['recommend_percentage'] < 40) {
            $concerns[] = [
                'title' => 'Low Employee Recommendation Rate',
                'description' => "Only {$metrics['recommend_percentage']}% of employees would recommend this company to a friend.",
                'severity' => $metrics['recommend_percentage'] < 30 ? 'high' : 'medium',
                'type' => 'metric',
            ];
        }

        return $concerns;
    }

    /**
     * Generate human-readable summary
     */
    protected function generateSummary(Company $company, array $metrics, array $aiInsights, string $severity): string
    {
        if ($severity === 'none') {
            return "No significant concerns detected for {$company->name} based on {$metrics['total_reviews']} reviews from the last {$metrics['time_window_days']} days.";
        }

        $parts = [];

        if ($metrics['rating_change'] && abs($metrics['rating_change']) >= 0.5) {
            $direction = $metrics['rating_change'] > 0 ? 'dropped' : 'increased';
            $parts[] = "Rating {$direction} by " . abs($metrics['rating_change']) . " stars";
        }

        $parts[] = "{$metrics['total_reviews']} reviews analyzed";

        if (!empty($aiInsights['red_flags'])) {
            $flagCount = count($aiInsights['red_flags']);
            $parts[] = "{$flagCount} red flag" . ($flagCount > 1 ? 's' : '') . " identified";
        }

        return ucfirst(implode('. ', $parts)) . '.';
    }
}
