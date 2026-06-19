<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('ai_sessions')->cascadeOnDelete();
            $table->uuidMorphs('actor');
            $table->string('role', 20)->index();
            $table->text('content');
            $table->json('attachment')->nullable();
            $table->json('suggestions')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('tokens_prompt')->nullable();
            $table->integer('tokens_completion')->nullable();
            $table->decimal('cost', 12, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_flagged')->default(false)->index();
            $table->text('flag_reason')->nullable();
            $table->bigInteger('flagged_at')->nullable();
            $table->bigInteger('created_at')->index();
            $table->bigInteger('updated_at');
            $table->bigInteger('deleted_at')->nullable()->index();

            $table->index(['session_id', 'role']);
            $table->index(['actor_type', 'actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
