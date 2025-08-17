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
        Schema::create('adjustment_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('adjustment_document_id')->constrained('adjustment_documents')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->unsignedBigInteger('unit_price_company_currency')->nullable();
            $table->unsignedBigInteger('subtotal_company_currency')->nullable();
            $table->unsignedBigInteger('total_line_tax_company_currency')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2);
            $table->bigInteger('unit_price'); // For MoneyCast
            $table->bigInteger('subtotal'); // For MoneyCast
            $table->bigInteger('total_line_tax'); // For MoneyCast
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_document_lines');
    }
};
