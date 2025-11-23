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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('service')->unique(); // gemini, newsapi, binance, etc.
            $table->text('key_value'); // Encrypted API key
            $table->text('secret_value')->nullable(); // For services with key+secret (e.g., Binance)
            $table->string('environment')->default('production'); // production, staging, development
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['service', 'environment', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
