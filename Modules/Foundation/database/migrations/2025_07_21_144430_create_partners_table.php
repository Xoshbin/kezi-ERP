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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('linked_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('type'); // 'customer', 'vendor', 'both'
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts');
            $table->foreignId('customer_payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete()->comment('Default payment terms when this partner is a customer');
            $table->foreignId('vendor_payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete()->comment('Default payment terms when this partner is a vendor');
            $table->foreignId('withholding_tax_type_id')->nullable()->constrained('withholding_tax_types')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts');
            $table->foreignId('fiscal_position_id')->nullable()->constrained('fiscal_positions')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
