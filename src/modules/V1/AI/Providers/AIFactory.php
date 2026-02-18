<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers;

use Exception;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\Providers\Anthropic\AnthropicService;
use Modules\V1\AI\Providers\Gemini\GeminiService;
use Modules\V1\AI\Providers\OpenAI\OpenAIService;

final class AIFactory
{
    /**
     * Create AI service instance based on provider
     *
     * @throws Exception
     */
    public static function make(?string $provider = null, array $config = []): AIServiceInterface
    {
        $provider = $provider ?? config('ai.default_provider', 'openai');

        return match (mb_strtolower($provider)) {
            'openai' => new OpenAIService(array_merge(
                config('ai.providers.openai', []),
                $config
            )),
            'anthropic', 'claude' => new AnthropicService(array_merge(
                config('ai.providers.anthropic', []),
                $config
            )),
            'gemini', 'google' => new GeminiService(array_merge(
                config('ai.providers.gemini', []),
                $config
            )),
            default => throw new Exception("Unsupported AI provider: {$provider}")
        };
    }

    /**
     * Get all available providers
     */
    public static function getAvailableProviders(): array
    {
        return ['openai', 'anthropic', 'gemini'];
    }

    /**
     * Check if provider is available
     */
    public static function isProviderAvailable(string $provider): bool
    {
        return in_array(mb_strtolower($provider), self::getAvailableProviders(), true);
    }
}
