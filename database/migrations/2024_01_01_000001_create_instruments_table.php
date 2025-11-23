<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('symbol', 20)->unique();
            $table->string('name');
            $table->enum('type', ['crypto', 'forex', 'stock', 'commodity']);
            $table->string('exchange', 50)->nullable();
            $table->string('sector', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Market data fields
            $table->decimal('market_cap', 20, 8)->nullable();
            $table->decimal('volume_24h', 20, 8)->nullable();
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('change_24h', 20, 8)->nullable();
            $table->decimal('change_percent_24h', 10, 4)->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['exchange', 'is_active']);
            $table->index('sector');
            $table->index('volume_24h');
            $table->index('change_percent_24h');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};