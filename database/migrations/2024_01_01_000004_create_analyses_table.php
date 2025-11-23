<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('instrument_id');
            
            // Core analysis results
            $table->decimal('final_score', 5, 4);
            $table->enum('recommendation', ['BUY', 'SELL', 'HOLD']);
            $table->decimal('confidence', 5, 4);
            $table->enum('time_horizon', ['short_term', 'medium_term', 'long_term']);
            $table->enum('risk_level', ['LOW', 'MEDIUM', 'HIGH']);
            
            // JSON fields for complex data
            $table->json('position_size_recommendation');
            $table->json('price_targets');
            $table->json('top_drivers');
            $table->json('evidence_sentences');
            $table->text('explainability_text');
            $table->text('risk_notes');
            $table->json('key_levels');
            $table->json('catalysts');
            $table->text('technical_summary');
            $table->text('fundamental_summary');
            $table->text('sentiment_summary');
            
            // Raw data storage
            $table->json('fusion_data')->nullable();
            $table->json('llm_metadata')->nullable();
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            
            $table->index(['user_id', 'created_at']);
            $table->index(['instrument_id', 'created_at']);
            $table->index('recommendation');
            $table->index('risk_level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};