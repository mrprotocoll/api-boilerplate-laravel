<?php

declare(strict_types=1);

namespace Modules\V1\AI\Providers;


use Illuminate\Support\ServiceProvider;
use Modules\V1\AI\Contracts\AIServiceInterface;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
//        $this->mergeConfigFrom(
//            __DIR__.'/../../config/ai.php',
//            'ai'
//        );

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
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/ai.php' => config_path('ai.php'),
        ], 'ai-config');
    }
}
