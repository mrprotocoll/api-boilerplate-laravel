<?php

declare(strict_types=1);

namespace Modules\V1\AI\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\V1\AI\Contracts\AIServiceInterface;
use Modules\V1\AI\DTO\AIResponse;

/**
 * @method static AIResponse complete(string $prompt, array $options = [])
 * @method static AIResponse structuredOutput(string $prompt, array $schema, array $options = [])
 * @method static AIResponse analyzeSentiment(string $text, array $options = [])
 * @method static AIResponse classify(string $text, array $categories, array $options = [])
 * @method static AIResponse extractEntities(string $text, array $options = [])
 * @method static string getProvider()
 * @method static string getModel()
 * @method static AIServiceInterface setModel(string $model)
 */
final class AI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai';
    }
}
