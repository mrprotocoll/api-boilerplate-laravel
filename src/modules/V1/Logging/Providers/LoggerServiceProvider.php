<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\V1\Logging\Services\ActivityLogger;
use Modules\V1\Logging\Services\CentralizedLogger;

final class LoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the centralized logger as a singleton
        $this->app->singleton(CentralizedLogger::class, function ($app) {
            return new CentralizedLogger();
        });

        $this->app->singleton(ActivityLogger::class, function ($app) {
            return new ActivityLogger($app->make(CentralizedLogger::class));
        });

        // Create an alias for easier access
        $this->app->alias(CentralizedLogger::class, 'centralized.logger');
        $this->app->alias(ActivityLogger::class, 'activity.logger');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Optional: You can add any boot logic here
        // For example, setting up database query logging
        if (config('logging.log_queries', false)) {
            $this->setupQueryLogging();
        }
    }

    /**
     * Setup automatic database query logging
     */
    protected function setupQueryLogging(): void
    {
        if (config('app.debug')) {
            \Illuminate\Support\Facades\DB::listen(function ($query): void {
                $logger = app(CentralizedLogger::class);
                $logger->query(
                    $query->sql,
                    $query->bindings,
                    $query->time
                );
            });
        }
    }
}
