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
        Schema::create('landed_cost_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('landed_cost_id')->constrained('landed_costs')->cascadeOnDelete();

            // The stock move that receives the additional cost
            $table->foreignId('stock_move_id')->constrained('stock_moves')->cascadeOnDelete();

            $table->bigInteger('additional_cost'); // BaseCurrencyMoneyCast

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landed_cost_lines');
    }
};
