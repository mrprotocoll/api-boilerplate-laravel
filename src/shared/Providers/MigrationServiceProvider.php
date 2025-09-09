<?php

declare(strict_types=1);

namespace Shared\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

final class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add custom Blueprint macros
        $this->registerBlueprintMacros();
    }

    /**
     * Register custom Blueprint macros for migrations
     */
    protected function registerBlueprintMacros(): void
    {
        // Macro for standard table setup with UUID primary key and bigint timestamps
        Blueprint::macro('standardColumns', function () {
            /** @var Blueprint $this */
            $this->uuid('id')->primary();
            $this->bigInteger('created_at')->useCurrent();
            $this->bigInteger('updated_at')->useCurrent();
            $this->bigInteger('deleted_at')->nullable()->index();

            return $this;
        });

        // Macro for audit columns (created_by, updated_by, deleted_by)
        Blueprint::macro('auditColumns', function () {
            /** @var Blueprint $this */
            $this->uuid('created_by')->nullable();
            $this->uuid('updated_by')->nullable();
            $this->uuid('deleted_by')->nullable();

            $this->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $this->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $this->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            return $this;
        });

        // Macro for common status and metadata columns
        Blueprint::macro('commonColumns', function () {
            /** @var Blueprint $this */
            $this->string('status')->default('active')->index();
            $this->json('metadata')->nullable();
            $this->bigInteger('sort_order')->default(0)->index();

            return $this;
        });

        // Drop standard columns
        Blueprint::macro('dropStandardColumns', function () {
            /** @var Blueprint $this */
            $this->dropColumn(['id', 'created_at', 'updated_at', 'deleted_at']);

            return $this;
        });

        // Drop audit columns
        Blueprint::macro('dropAuditColumns', function () {
            /** @var Blueprint $this */
            $this->dropForeign(['created_by']);
            $this->dropForeign(['updated_by']);
            $this->dropForeign(['deleted_by']);
            $this->dropColumn(['created_by', 'updated_by', 'deleted_by']);

            return $this;
        });
    }
}
