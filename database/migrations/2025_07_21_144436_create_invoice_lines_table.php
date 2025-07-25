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
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('tax_id')->nullable()->constrained('taxes');
            $table->foreignId('income_account_id')->constrained('accounts');
            $table->string('description');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total_line_tax');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
