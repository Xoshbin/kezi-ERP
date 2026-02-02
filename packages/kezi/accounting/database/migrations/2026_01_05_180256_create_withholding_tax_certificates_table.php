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
        Schema::create('withholding_tax_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->foreignId('vendor_id')->constrained('partners');
            $table->date('certificate_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('total_base_amount'); // Minor units - sum of base amounts
            $table->bigInteger('total_withheld_amount'); // Minor units - sum of withheld amounts
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('status')->default('draft'); // draft, issued, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'vendor_id']);
            $table->index(['company_id', 'certificate_date']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_certificates');
    }
};
