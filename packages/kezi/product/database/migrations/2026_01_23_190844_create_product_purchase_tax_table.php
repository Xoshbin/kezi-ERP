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
        Schema::create('product_purchase_tax', function (Blueprint $table) {
            $table->foreignIdFor(\Kezi\Product\Models\Product::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\Kezi\Accounting\Models\Tax::class)->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tax_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_purchase_tax');
    }
};
