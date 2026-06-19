<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\V1\AI\Contracts\AIServiceInterface;

final class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('ai.php'), 'ai');

        $this->app->singleton(AIServiceInterface::class, function ($app) {
            return AIFactory::make();
        });

        // Alias to string binding used by facade
        $this->app->alias(AIServiceInterface::class, 'ai');
    }


    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configuration lives in config/ai.php for this application boilerplate.
    }
}
