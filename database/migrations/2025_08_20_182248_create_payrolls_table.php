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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');

            // Payroll Period
            $table->string('payroll_number')->unique();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->date('pay_date');
            $table->string('pay_frequency'); // monthly, bi_weekly, weekly

            // Salary Components (using Money pattern)
            $table->unsignedBigInteger('base_salary');
            $table->unsignedBigInteger('overtime_amount')->default(0);
            $table->unsignedBigInteger('housing_allowance')->default(0);
            $table->unsignedBigInteger('transport_allowance')->default(0);
            $table->unsignedBigInteger('meal_allowance')->default(0);
            $table->unsignedBigInteger('other_allowances')->default(0);
            $table->unsignedBigInteger('bonus')->default(0);
            $table->unsignedBigInteger('commission')->default(0);

            // Deductions
            $table->unsignedBigInteger('income_tax')->default(0);
            $table->unsignedBigInteger('social_security')->default(0);
            $table->unsignedBigInteger('health_insurance')->default(0);
            $table->unsignedBigInteger('pension_contribution')->default(0);
            $table->unsignedBigInteger('loan_deduction')->default(0);
            $table->unsignedBigInteger('other_deductions')->default(0);

            // Totals
            $table->unsignedBigInteger('gross_salary');
            $table->unsignedBigInteger('total_deductions');
            $table->unsignedBigInteger('net_salary');

            // Multi-currency support (following VendorBill pattern)
            $table->decimal('exchange_rate_at_processing', 20, 10)->nullable();
            $table->unsignedBigInteger('gross_salary_company_currency')->nullable();
            $table->unsignedBigInteger('net_salary_company_currency')->nullable();

            // Working Hours
            $table->decimal('regular_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('total_hours', 5, 2)->default(0);

            // Status and Processing
            $table->string('status')->default('draft'); // draft, processed, paid, cancelled
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Notes and Adjustments
            $table->text('notes')->nullable();
            $table->json('adjustments')->nullable(); // Manual adjustments with reasons

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['employee_id']);
            $table->index(['period_start_date', 'period_end_date']);
            $table->index(['pay_date']);
            $table->index(['processed_by_user_id']);

            // Unique constraint to prevent duplicate payrolls
            $table->unique(['employee_id', 'period_start_date', 'period_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
