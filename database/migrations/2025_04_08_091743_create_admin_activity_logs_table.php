<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_id')->references('id')->on('system_users')->cascadeOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable(); // e.g., User, Contract
            $table->uuid('model_id')->nullable();
            $table->json('meta')->nullable(); // Extra metadata
            $table->bigInteger('created_at')->useCurrent();
            $table->bigInteger('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
