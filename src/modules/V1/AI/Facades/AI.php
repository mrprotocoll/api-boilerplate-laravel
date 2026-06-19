<?php

declare(strict_types=1);

namespace Modules\V1\AI\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\DTO\AIResponse;

/**
 * @method static AIResponse complete(string $prompt, array $options = [])
 * @method static AIResponse chat(array $messages, array $options = [])
 * @method static AIResponse chatWithTools(array $messages, array $tools, array $options = [])
 * @method static AIResponse streamChatWithTools(array $messages, array $tools, array $options, callable $onDelta)
 * @method static AIResponse structuredOutput(string $prompt, array $schema, array $options = [])
 * @method static AIResponse analyzeSentiment(string $text, array $options = [])
 * @method static AIResponse classify(string $text, array $categories, array $options = [])
 * @method static AIResponse extractEntities(string $text, array $options = [])
 * @method static string getProvider()
 * @method static string getModel()
 * @method static AIServiceInterface setModel(string $model)
 * @method static bool supportsTools()
 * @method static bool supportsStreaming()
 * @method static bool supportsStructuredOutput()
 */
final class AI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai';
    }
}
