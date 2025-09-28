<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts'); // For accounting integration

            // Line Item Information
            $table->string('line_type'); // earning, deduction, tax, contribution
            $table->string('code'); // salary, overtime, tax, insurance, etc.
            $table->json('description'); // Translatable description

            // Calculation Details
            $table->decimal('quantity', 10, 4)->default(1); // Hours, days, percentage, etc.
            $table->string('unit')->nullable(); // hours, days, percentage, fixed
            $table->unsignedBigInteger('rate')->nullable(); // Rate per unit (Money field)
            $table->unsignedBigInteger('amount'); // Final calculated amount (Money field)

            // Multi-currency support
            $table->unsignedBigInteger('amount_company_currency')->nullable();

            // Tax and Contribution Details
            $table->decimal('tax_rate', 5, 2)->nullable(); // For tax calculations
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false); // Required by law

            // Accounting Integration
            $table->string('debit_credit'); // debit or credit for journal entry
            $table->foreignId('analytic_account_id')->nullable()->constrained('analytic_accounts');

            // Notes and References
            $table->text('notes')->nullable();
            $table->string('reference')->nullable(); // External reference

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id']);
            $table->index(['payroll_id']);
            $table->index(['account_id']);
            $table->index(['line_type']);
            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
    }
};
