<?php

declare(strict_types=1);

namespace Modules\V1\AI\Services\EmergencyConcerns;

use Illuminate\Support\Facades\Cache;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\Providers\AIFactory;
use Modules\V1\Company\Models\Company;
use Modules\V1\Company\Models\InterviewReview;
use Modules\V1\Company\Models\Review;
use Modules\V1\Company\Models\SalaryReview;

class EmergencyConcernSignalsAnalyzer
{
    protected AIServiceInterface $ai;
    protected int $cacheTTL = 3600; // 1 hour
    protected float $criticalThreshold = 1.2; // Rating drop threshold
    protected int $timeWindow = 30; // Days to analyze
    protected int $minReviewsForConcern = 15;

    public function __construct(?string $provider = null)
    {
        $this->ai = AIFactory::make($provider);
    }

    /**
     * Get all companies with emergency concern signals
     *
     * @param array $options
     * @return array
     */
    public function getAllConcernSignals(array $options = []): array
    {
        $cacheKey = 'emergency_concern_signals_all';

        if (config('ai.cache.enabled')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $companies = Company::all();
        $concerns = [];

        foreach ($companies as $company) {
            $concern = $this->analyzeCompany($company->id);

            if ($concern && $concern['has_concern']) {
                $concerns[] = $concern;
            }
        }

        // Sort by severity
        usort($concerns, function ($a, $b) {
            return $this->getSeverityOrder($b['severity']) - $this->getSeverityOrder($a['severity']);
        });

        if (config('ai.cache.enabled')) {
            Cache::put($cacheKey, $concerns, $this->cacheTTL);
        }

        return $concerns;
    }

    /**
     * Analyze a specific company for concern signals
     *
     * @param int $companyId
     * @return array|null
     */
    public function analyzeCompany(int $companyId): ?array
    {
        $company = Company::find($companyId);

        if (!$company) {
            return null;
        }

        // Get recent reviews
        $recentReviews = $this->getRecentReviews($company, $this->timeWindow);
        $totalRecentReviews = count($recentReviews);

        if ($totalRecentReviews < $this->minReviewsForConcern) {
            return null;
        }

        // Calculate rating changes
        $ratingAnalysis = $this->analyzeRatingChanges($company, $recentReviews);

        // Check for toxic leadership mentions
        $toxicityAnalysis = $this->analyzeToxicLeadership($recentReviews);

        // Determine if there's a concern
        $hasConcern = $ratingAnalysis['has_dropped'] || $toxicityAnalysis['has_toxic_mentions'];

        if (!$hasConcern) {
            return null;
        }

        // Determine severity
        $severity = $this->determineSeverity(
            $ratingAnalysis['rating_drop'],
            $totalRecentReviews,
            $toxicityAnalysis['toxic_count']
        );

        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'has_concern' => true,
            'severity' => $severity,
            'rating_drop' => round($ratingAnalysis['rating_drop'], 1),
            'time_window_days' => $this->timeWindow,
            'recent_reviews_count' => $totalRecentReviews,
            'toxic_leadership_mentions' => $toxicityAnalysis['toxic_count'],
            'message' => $this->generateConcernMessage(
                $ratingAnalysis['rating_drop'],
                $totalRecentReviews,
                $toxicityAnalysis['has_toxic_mentions']
            ),
            'analyzed_at' => now()->toISOString(),
        ];
    }

    /**
     * Get detailed concern analysis using AI
     *
     * @param int $companyId
     * @return array
     */
    public function getDetailedAnalysis(int $companyId): array
    {
        $company = Company::find($companyId);

        if (!$company) {
            return ['error' => 'Company not found'];
        }

        $recentReviews = $this->getRecentReviews($company, $this->timeWindow);

        // Extract review texts
        $reviewTexts = array_map(function ($review) {
            $texts = [];
            if (!empty($review['pros'])) $texts[] = "Pros: " . $review['pros'];
            if (!empty($review['cons'])) $texts[] = "Cons: " . $review['cons'];
            if (!empty($review['suggestions'])) $texts[] = "Suggestions: " . $review['suggestions'];
            return implode("\n", $texts);
        }, $recentReviews);

        $combinedText = implode("\n\n---\n\n", array_slice($reviewTexts, 0, 20)); // Limit to 20 reviews

        $prompt = "Analyze these recent employee reviews for {$company->name} and identify key concerns, patterns, and issues. Focus on:\n"
            . "1. Leadership and management issues\n"
            . "2. Workplace culture problems\n"
            . "3. Compensation and benefits concerns\n"
            . "4. Work-life balance issues\n"
            . "5. Any red flags or alarming patterns\n\n"
            . "Reviews:\n{$combinedText}\n\n"
            . "Provide a structured analysis with specific examples.";

        $response = $this->ai->structuredOutput($prompt, [
            'name' => 'concern_analysis',
            'type' => 'object',
            'properties' => [
                'key_concerns' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                            'frequency' => ['type' => 'string']
                        ],
                        'required' => ['category', 'description', 'severity'],
                        'additionalProperties' => false
                    ]
                ],
                'overall_sentiment' => [
                    'type' => 'string',
                    'enum' => ['very_negative', 'negative', 'neutral', 'positive', 'very_positive']
                ],
                'red_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'summary' => ['type' => 'string']
            ],
            'required' => ['key_concerns', 'overall_sentiment', 'red_flags', 'summary'],
            'additionalProperties' => false
        ]);

        return array_merge(
            $response->getStructured() ?? [],
            [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'reviews_analyzed' => count($reviewTexts),
                'analyzed_at' => now()->toISOString(),
            ]
        );
    }

    /**
     * Get recent reviews for a company
     *
     * @param Company $company
     * @param int $days
     * @return array
     */
    protected function getRecentReviews(Company $company, int $days): array
    {
        $startDate = now()->subDays($days)->timestamp;
        $reviews = [];

        // Company reviews
        $companyReviews = Review::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->map(function ($review) {
                $overallRating = $review->ratings()
                    ->where('category', 'overall')
                    ->first();

                return [
                    'type' => 'company',
                    'id' => $review->id,
                    'rating' => $overallRating?->rating ?? null,
                    'pros' => $review->pros,
                    'cons' => $review->cons,
                    'suggestions' => $review->suggestions,
                    'recommend_to_friend' => $review->recommend_to_friend,
                    'ceo_approval' => $review->ceo_approval,
                    'created_at' => $review->created_at,
                ];
            })
            ->toArray();

        $reviews = array_merge($reviews, $companyReviews);

        // Salary reviews
        $salaryReviews = SalaryReview::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'salary',
                    'id' => $review->id,
                    'rating' => null,
                    'created_at' => $review->created_at,
                ];
            })
            ->toArray();

        $reviews = array_merge($reviews, $salaryReviews);

        // Interview reviews
        $interviewReviews = InterviewReview::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->map(function ($review) {
                return [
                    'type' => 'interview',
                    'id' => $review->id,
                    'rating' => null,
                    'experience_description' => $review->experience_description ?? '',
                    'created_at' => $review->created_at,
                ];
            })
            ->toArray();

        $reviews = array_merge($reviews, $interviewReviews);

        return $reviews;
    }

    /**
     * Analyze rating changes
     *
     * @param Company $company
     * @param array $recentReviews
     * @return array
     */
    protected function analyzeRatingChanges(Company $company, array $recentReviews): array
    {
        // Get current average rating (last 30 days)
        $recentRatings = array_filter(array_column($recentReviews, 'rating'));
        $currentAvg = count($recentRatings) > 0 ? array_sum($recentRatings) / count($recentRatings) : 0;

        // Get previous average rating (30-60 days ago)
        $previousStart = now()->subDays($this->timeWindow * 2)->timestamp;
        $previousEnd = now()->subDays($this->timeWindow)->timestamp;

        $previousReviews = Review::where('company_id', $company->id)
            ->where('status', 'submitted')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->with(['ratings' => function ($q) {
                $q->where('category', 'overall');
            }])
            ->get();

        $previousRatings = [];
        foreach ($previousReviews as $review) {
            $overallRating = $review->ratings->where('category', 'overall')->first();
            if ($overallRating) {
                $previousRatings[] = $overallRating->rating;
            }
        }

        $previousAvg = count($previousRatings) > 0 ? array_sum($previousRatings) / count($previousRatings) : $currentAvg;

        $ratingDrop = $previousAvg - $currentAvg;
        $hasDropped = $ratingDrop >= $this->criticalThreshold;

        return [
            'current_avg' => $currentAvg,
            'previous_avg' => $previousAvg,
            'rating_drop' => $ratingDrop,
            'has_dropped' => $hasDropped,
        ];
    }

    /**
     * Analyze toxic leadership mentions
     *
     * @param array $reviews
     * @return array
     */
    protected function analyzeToxicLeadership(array $reviews): array
    {
        $toxicKeywords = [
            'toxic', 'harassment', 'bully', 'bullying', 'discrimination',
            'hostile', 'micromanage', 'micromanagement', 'abuse', 'abusive',
            'unfair', 'favoritism', 'nepotism', 'retaliation'
        ];

        $toxicCount = 0;

        foreach ($reviews as $review) {
            $text = strtolower(
                ($review['pros'] ?? '') . ' ' .
                ($review['cons'] ?? '') . ' ' .
                ($review['suggestions'] ?? '') . ' ' .
                ($review['experience_description'] ?? '')
            );

            foreach ($toxicKeywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $toxicCount++;
                    break; // Count once per review
                }
            }
        }

        return [
            'toxic_count' => $toxicCount,
            'has_toxic_mentions' => $toxicCount >= 3, // At least 3 reviews mentioning toxic behavior
        ];
    }

    /**
     * Determine severity level
     *
     * @param float $ratingDrop
     * @param int $reviewCount
     * @param int $toxicCount
     * @return string
     */
    protected function determineSeverity(float $ratingDrop, int $reviewCount, int $toxicCount): string
    {
        $score = 0;

        // Rating drop contribution
        if ($ratingDrop >= 2.0) {
            $score += 40;
        } elseif ($ratingDrop >= 1.5) {
            $score += 30;
        } elseif ($ratingDrop >= 1.2) {
            $score += 20;
        }

        // Review volume contribution
        if ($reviewCount >= 30) {
            $score += 20;
        } elseif ($reviewCount >= 20) {
            $score += 15;
        } elseif ($reviewCount >= 15) {
            $score += 10;
        }

        // Toxic mentions contribution
        if ($toxicCount >= 10) {
            $score += 40;
        } elseif ($toxicCount >= 5) {
            $score += 30;
        } elseif ($toxicCount >= 3) {
            $score += 20;
        }

        // Determine severity based on score
        if ($score >= 70) {
            return 'critical';
        } elseif ($score >= 50) {
            return 'high';
        } elseif ($score >= 30) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get severity order for sorting
     *
     * @param string $severity
     * @return int
     */
    protected function getSeverityOrder(string $severity): int
    {
        return match ($severity) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Generate concern message
     *
     * @param float $ratingDrop
     * @param int $reviewCount
     * @param bool $hasToxicMentions
     * @return string
     */
    protected function generateConcernMessage(float $ratingDrop, int $reviewCount, bool $hasToxicMentions): string
    {
        $parts = [];

        $parts[] = "Rating dropped " . round($ratingDrop, 1) . "★ in {$this->timeWindow} days.";
        $parts[] = "{$reviewCount} reviews cite";

        if ($hasToxicMentions) {
            $parts[] = "toxic leadership";
        } else {
            $parts[] = "concerns";
        }

        return implode(' ', $parts);
    }

    /**
     * Set time window for analysis
     *
     * @param int $days
     * @return self
     */
    public function setTimeWindow(int $days): self
    {
        $this->timeWindow = $days;
        return $this;
    }

    /**
     * Set minimum reviews threshold
     *
     * @param int $count
     * @return self
     */
    public function setMinReviewsThreshold(int $count): self
    {
        $this->minReviewsForConcern = $count;
        return $this;
    }

    /**
     * Set critical rating drop threshold
     *
     * @param float $threshold
     * @return self
     */
    public function setCriticalThreshold(float $threshold): self
    {
        $this->criticalThreshold = $threshold;
        return $this;
    }
}
