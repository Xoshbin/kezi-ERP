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
        Schema::create('withholding_tax_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('withholding_tax_type_id')->constrained('withholding_tax_types')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('partners');
            $table->bigInteger('base_amount'); // Minor units - gross amount subject to WHT
            $table->bigInteger('withheld_amount'); // Minor units - calculated WHT amount
            $table->decimal('rate_applied', 5, 4); // Rate at time of withholding for audit trail
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('withholding_tax_certificate_id')->nullable()->constrained('withholding_tax_certificates');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'vendor_id']);
            $table->index(['company_id', 'withholding_tax_type_id']);
            $table->index(['withholding_tax_certificate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_entries');
    }
};
