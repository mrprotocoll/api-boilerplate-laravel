<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers\OpenAI;

use Exception;
use Generator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Modules\V1\AI\Exceptions\InvalidResponseException;
use OpenAI\Laravel\Facades\OpenAI as OpenAIClient;
use OpenAI\Responses\Chat\CreateResponse;
use Shared\Helpers\GlobalHelper;

final class OpenAI
{

    private readonly string $apiKey;

    private readonly string $model;

    private readonly int $maxTokens;

    private float $temperature = 0.8;

    public function __construct(private array $config)
    {
        $this->apiKey = $this->config['api_key'];
        $this->model = $this->config['model'];
        $this->maxTokens = (int) $this->config['max_tokens'];
    }

    /**
     * @throws Exception
     */
    private function generateStructuredContent(string $systemPrompt, string $userPrompt, string $responseKey): array
    {
        $conversationContext = [
            'conversation_id' => $conversationId ?? uniqid('calendar_', true),
            'timestamp' => now()->toISOString(),
            'type' => 'content_calendar_generation'
        ];

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt  . "\n\nConversation Context: " . json_encode($conversationContext) ],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $requestData = [
            'model' => $this->model,
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
            'max_tokens' => 4096,
            'temperature' => 0.7,
        ];

        $response = OpenAIClient::chat()->create($requestData);

        return $this->handleJsonResponse($response, $messages, $requestData, $responseKey);
    }

    /**
     * @throws InvalidResponseException
     * @throws \JsonException
     */
    public function analyzeJson(string $systemPrompt, string $userPrompt, string $responseKey = null): array
    {
        $response = $this->generateStructuredContent($systemPrompt, $userPrompt, $responseKey ?? 'data');

        return $this->parseAiResponse($response, $responseKey ?? 'data');
    }

    /**
     * Analyze document with vision capabilities
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param string $base64Image
     * @return array
     * @throws Exception
     */
    public function analyzeDocumentWithVision(string $systemPrompt, string $userPrompt, string $base64Image): array
    {
        $conversationContext = [
            'conversation_id' => uniqid('tenancy_analysis_', true),
            'timestamp' => now()->toISOString(),
            'type' => 'tenancy_agreement_validation'
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt . "\n\nConversation Context: " . json_encode($conversationContext)
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userPrompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:image/jpeg;base64,{$base64Image}"
                        ]
                    ]
                ]
            ]
        ];

        $requestData = [
            'model' => 'gpt-4o-mini', // Use gpt-4o-mini for vision capabilities
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
            'max_tokens' => 4096,
            'temperature' => 0.3, // Lower temperature for more consistent analysis
        ];

        try {
            $response = OpenAIClient::chat()->create($requestData);
            return $this->handleJsonResponse($response, $messages, $requestData, 'extracted_data');
        } catch (Exception $e) {
            Log::error('OpenAI Vision API Error: ' . $e->getMessage());
            throw new Exception('Failed to analyze document. Please try again.');
        }
    }


    /**
     * Analyze text document (for PDFs)
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @return CreateResponse
     * @throws Exception
     */
    public function analyzeTextDocument(string $systemPrompt, string $userPrompt): array
    {
        $conversationContext = [
            'conversation_id' => uniqid('tenancy_text_analysis_', true),
            'timestamp' => now()->toISOString(),
            'type' => 'tenancy_agreement_text_validation'
        ];

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt . "\n\nConversation Context: " . json_encode($conversationContext)
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ];

        $requestData = [
            'model' => $this->model, // Use your configured model (gpt-4o-mini)
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
            'max_tokens' => 5096,
            'temperature' => 0.3,
        ];

        try {
            $response = OpenAIClient::chat()->create($requestData);
            return $this->handleJsonResponse($response, $messages, $requestData, 'extracted_data');
        } catch (Exception $e) {
            Log::error('OpenAI Text Analysis Error: ' . $e->getMessage());
            throw new Exception('Failed to analyze document text. Please try again.');
        }
    }

    private function handleJsonResponse(CreateResponse $response, array $messages, array $requestData, string $responseKey): array
    {
        $json = $response->choices[0]->message->content;
        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            Log::info('json_incomplete: ', [
                'content' => $response->choices[0]->message,
                'error' => json_last_error_msg(),
            ]);

            // Ask the model to continue and finish the JSON
            $messages[] = [
                'role' => 'assistant',
                'content' => $json,
            ];
            $messages[] = [
                'role' => 'user',
                'content' => 'The previous JSON response was incomplete or invalid. Please provide a complete, valid JSON response with no trailing commas or incomplete values.',
            ];

            $requestData['messages'] = $messages;
            $response = OpenAIClient::chat()->create($requestData);

            $json = $response->choices[0]->message->content;
            $decoded = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                Log::error('json_still_invalid_after_retry', [
                    'error' => json_last_error_msg(),
                    'json' => $json,
                ]);

                throw new Exception('Json failed, try again later');
            }
        }

//        $content = $this->parseAiResponse($response, $responseKey);
        return $decoded;
    }


    private function parseAiResponse(object $response, string $responseKey = null): array
    {
        $content = $response->choices[0]->message->content;
        $content = GlobalHelper::cleanJsonString($content);
        $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

        if ( ! $decoded) {
            throw new InvalidResponseException('Invalid JSON response from AI');
        }

        return $responseKey ? $decoded->{$responseKey} ?? [] : $decoded;
    }
}
