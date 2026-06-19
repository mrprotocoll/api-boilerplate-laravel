<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ai_tool_calls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('ai_sessions')->cascadeOnDelete();
            $table->foreignUuid('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->uuidMorphs('actor');
            $table->string('tool', 100)->index();
            $table->json('arguments')->nullable();
            $table->string('status', 30)->index();
            $table->boolean('authorized')->default(false);
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('result_meta')->nullable();
            $table->bigInteger('created_at')->index();
            $table->bigInteger('updated_at');
            $table->bigInteger('deleted_at')->nullable()->index();

            $table->index(['session_id', 'tool']);
            $table->index(['actor_type', 'actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_calls');
    }
};
