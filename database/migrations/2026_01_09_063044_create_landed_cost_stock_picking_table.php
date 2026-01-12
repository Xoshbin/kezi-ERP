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
        Schema::create('landed_cost_stock_picking', function (Blueprint $table) {
            $table->foreignId('landed_cost_id')->constrained('landed_costs')->cascadeOnDelete();
            $table->foreignId('stock_picking_id')->constrained('stock_pickings')->cascadeOnDelete();

            $table->primary(['landed_cost_id', 'stock_picking_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landed_cost_stock_picking');
    }
};
