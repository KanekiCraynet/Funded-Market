<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('instrument_id');
            $table->timestamp('timestamp');
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);
            $table->decimal('volume', 20, 2);
            $table->decimal('adjusted_close', 20, 8)->nullable();
            $table->string('timeframe', 10); // 1m, 5m, 15m, 1h, 4h, 1d, 1w
            $table->string('source', 50); // coingecko, yahoo, alpha_vantage, etc.
            $table->timestamps();
            
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
            $table->index(['instrument_id', 'timestamp']);
            $table->index(['instrument_id', 'timeframe']);
            $table->index('timestamp');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};