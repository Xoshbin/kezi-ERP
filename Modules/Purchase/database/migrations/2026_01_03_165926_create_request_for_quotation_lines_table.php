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
        Schema::create('request_for_quotation_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rfq_id')->constrained('request_for_quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('tax_id')->nullable()->constrained('taxes');

            $table->string('description');
            $table->decimal('quantity', 15, 4);
            $table->string('unit')->nullable();

            // Pricing (can be 0 initially if asking for price)
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('subtotal');
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_quotation_lines');
    }
};
