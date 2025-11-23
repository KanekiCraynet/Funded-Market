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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event_type', ['llm_request', 'rate_limit', 'error', 'user_action']);
            $table->json('context');
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for efficient querying
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index(['event_type', 'severity', 'created_at'], 'idx_event_severity_created');
            $table->index(['created_at'], 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
