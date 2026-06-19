<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('session_token', 100)->unique();
            $table->uuidMorphs('actor');
            $table->string('status', 20)->default('active')->index();
            $table->string('source_page')->nullable();
            $table->bigInteger('last_activity_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->bigInteger('created_at');
            $table->bigInteger('updated_at');
            $table->bigInteger('deleted_at')->nullable()->index();

            $table->index(['actor_type', 'actor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_sessions');
    }
};
