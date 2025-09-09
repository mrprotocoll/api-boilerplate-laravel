<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('log_name')->nullable()->index(); // Group activities by type
            $table->text('description');
            $table->string('event')->nullable()->index(); // created, updated, deleted, etc.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Who performed the action
            $table->jsonb('properties')->nullable(); // Additional data
            $table->jsonb('old_values')->nullable(); // Before values for updates
            $table->jsonb('new_values')->nullable(); // After values for updates
            $table->string('batch_uuid')->nullable()->index(); // Group related activities
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->string('subject_type')->nullable(); // e.g., User, Contract
            $table->uuid('subject_id')->nullable();
            $table->uuid('id')->primary();
            $table->bigInteger('created_at')->useCurrent();
            $table->bigInteger('updated_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['log_name', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
