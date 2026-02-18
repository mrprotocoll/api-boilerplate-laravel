<?php

namespace Modules\V1\AI\Contracts;

use Modules\V1\AI\DTO\AIResponse;

interface AIServiceInterface
{
    /**
     * Generate text completion
     *
     * @param string $prompt
     * @param array $options
     * @return AIResponse
     */
    public function complete(string $prompt, array $options = []): AIResponse;

    /**
     * Generate structured output
     *
     * @param string $prompt
     * @param array $schema
     * @param array $options
     * @return AIResponse
     */
    public function structuredOutput(string $prompt, array $schema, array $options = []): AIResponse;

    /**
     * Analyze sentiment
     *
     * @param string $text
     * @param array $options
     * @return AIResponse
     */
    public function analyzeSentiment(string $text, array $options = []): AIResponse;

    /**
     * Classify text
     *
     * @param string $text
     * @param array $categories
     * @param array $options
     * @return AIResponse
     */
    public function classify(string $text, array $categories, array $options = []): AIResponse;

    /**
     * Extract entities from text
     *
     * @param string $text
     * @param array $options
     * @return AIResponse
     */
    public function extractEntities(string $text, array $options = []): AIResponse;

    /**
     * Get provider name
     *
     * @return string
     */
    public function getProvider(): string;

    /**
     * Get model being used
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Set model to use
     *
     * @param string $model
     * @return self
     */
    public function setModel(string $model): self;
}
