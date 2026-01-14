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
        Schema::create('vendor_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vendor_bill_id')->constrained('vendor_bills')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('tax_id')->nullable()->constrained('taxes');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('analytic_account_id')->nullable()->constrained('analytic_accounts');
            $table->string('shipping_cost_type')->nullable();
            $table->string('description');
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total_line_tax');
            $table->date('deferred_start_date')->nullable();
            $table->date('deferred_end_date')->nullable();
            // Add company currency amounts (converted amounts)
            $table->unsignedBigInteger('unit_price_company_currency')->nullable();
            $table->unsignedBigInteger('subtotal_company_currency')->nullable();
            $table->unsignedBigInteger('total_line_tax_company_currency')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_lines');
    }
};
