<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_quants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->constrained('stock_locations');
            $table->foreignId('lot_id')->nullable()->constrained('lots')->cascadeOnDelete();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('reserved_quantity', 18, 4)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'product_id', 'location_id', 'lot_id'], 'uq_quants_company_product_location_lot');
            $table->index(['product_id', 'location_id']);
            $table->index(['company_id', 'product_id']);
            $table->index(['lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_quants');
    }
};
