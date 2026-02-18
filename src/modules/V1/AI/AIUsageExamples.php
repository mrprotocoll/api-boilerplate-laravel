<?php

namespace Modules\V1\AI;

use Modules\V1\AI\Facades\AI;
use Modules\V1\AI\Providers\AIFactory;

class AIUsageExamples
{
    /**
     * Example 1: Basic Text Completion
     */
    public function basicCompletion()
    {

        $response = AI::complete('Write a professional email requesting a meeting');

        echo "Response: " . $response->getContent() . "\n";
        echo "Tokens: " . $response->getTotalTokens() . "\n";
        echo "Cost: $" . number_format($response->getCost(), 4) . "\n";
    }

    /**
     * Example 2: Structured Output - Extract Data
     */
    public function structuredDataExtraction()
    {

        $jobPosting = "Senior Software Engineer at TechCorp. Located in San Francisco. Salary: $150k-200k. Required: 5+ years Python, AWS, Docker.";

        $response = AI::structuredOutput(
            "Extract structured information from this job posting: {$jobPosting}",
            [
                'name' => 'job_extraction',
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'location' => ['type' => 'string'],
                    'salary_min' => ['type' => 'integer'],
                    'salary_max' => ['type' => 'integer'],
                    'requirements' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ]
                ],
                'required' => ['title', 'company'],
                'additionalProperties' => false
            ]
        );

        $data = $response->getStructured();
        print_r($data);
    }

    /**
     * Example 3: Sentiment Analysis
     */
    public function analyzeSentiment()
    {

        $review = "The product quality is amazing but the customer service was terrible.";

        $response = AI::analyzeSentiment($review);
        $result = $response->getStructured();

        echo "Sentiment: {$result['sentiment']}\n";
        echo "Score: {$result['score']}\n";
        echo "Confidence: {$result['confidence']}\n";
    }

    /**
     * Example 4: Classification
     */
    public function classifyContent()
    {

        $text = "Breaking news: Scientists discover new exoplanet in habitable zone";

        $response = AI::classify(
            $text,
            ['technology', 'science', 'politics', 'sports', 'entertainment']
        );

        $result = $response->getStructured();
        echo "Category: {$result['category']}\n";
        echo "Confidence: {$result['confidence']}\n";
    }

    /**
     * Example 5: Entity Extraction
     */
    public function extractEntities()
    {

        $text = "Tim Cook announced new products at Apple Park in Cupertino, California.";

        $response = AI::extractEntities($text);
        $result = $response->getStructured();

        foreach ($result['entities'] as $entity) {
            echo "{$entity['text']} ({$entity['type']}) - Confidence: {$entity['confidence']}\n";
        }
    }

    /**
     * Example 6: Switch Providers
     */
    public function switchProviders()
    {
        $prompt = "Explain quantum computing in one sentence.";

        // OpenAI
        $openai = AIFactory::make('openai');
        $response1 = $openai->complete($prompt);
        echo "OpenAI: " . $response1->getContent() . "\n\n";

        // Anthropic
        $anthropic = AIFactory::make('anthropic');
        $response2 = $anthropic->complete($prompt);
        echo "Anthropic: " . $response2->getContent() . "\n\n";

        // Gemini
        $gemini = AIFactory::make('gemini');
        $response3 = $gemini->complete($prompt);
        echo "Gemini: " . $response3->getContent() . "\n\n";
    }

    /**
     * Example 9: Custom Configuration
     */
    public function customConfiguration()
    {
        $ai = AIFactory::make('openai', [
            'model' => 'gpt-4o',
            'temperature' => 0.3,
            'max_tokens' => 500,
        ]);

        $response = $ai->complete('Generate a creative story about AI');
        echo $response->getContent();
    }

    /**
     * Example 10: Batch Processing
     */
    public function batchProcessing()
    {

        $reviews = [
            "Great product, highly recommend!",
            "Terrible experience, waste of money.",
            "It's okay, nothing special.",
            "Exceeded my expectations!",
            "Worst purchase ever."
        ];

        foreach ($reviews as $review) {
            $response = AI::analyzeSentiment($review);
            $result = $response->getStructured();

            echo "Review: {$review}\n";
            echo "Sentiment: {$result['sentiment']} (Score: {$result['score']})\n\n";
        }
    }

    /**
     * Example 11: Error Handling
     */
    public function errorHandling()
    {
        try {
            $ai = AIFactory::make('invalid_provider');
        } catch (\Exception $e) {
            echo "Provider error: " . $e->getMessage() . "\n";
        }

        try {
            $response = AI::complete('Test prompt');
            echo "Success: " . $response->getContent() . "\n";
        } catch (\Exception $e) {
            echo "API error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Example 12: Cost Tracking
     */
    public function costTracking()
    {

        $prompts = [
            "Write a haiku about programming",
            "Explain machine learning",
            "Generate product description"
        ];

        $totalCost = 0;
        $totalTokens = 0;

        foreach ($prompts as $prompt) {
            $response = AI::complete($prompt);
            $totalCost += $response->getCost();
            $totalTokens += $response->getTotalTokens();

            echo "Prompt: {$prompt}\n";
            echo "Cost: $" . number_format($response->getCost(), 4) . "\n";
            echo "Tokens: {$response->getTotalTokens()}\n\n";
        }

        echo "Total Cost: $" . number_format($totalCost, 4) . "\n";
        echo "Total Tokens: {$totalTokens}\n";
    }
}
