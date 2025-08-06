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
        Schema::create('stock_move_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('stock_move_id')->constrained('stock_moves');
            $table->decimal('quantity', 15, 4);
            $table->decimal('cost_impact', 15, 4);
            $table->string('valuation_method');
            $table->string('move_type');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->morphs('source');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_move_valuations');
    }
};
