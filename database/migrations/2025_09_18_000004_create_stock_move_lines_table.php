<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_move_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_move_product_line_id')->constrained('stock_move_product_lines')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->cascadeOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->timestamps();

            $table->unique(['stock_move_product_line_id', 'lot_id'], 'uniq_product_line_lot');
            $table->index(['company_id', 'lot_id']);
            $table->index(['stock_move_product_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_move_lines');
    }
};
