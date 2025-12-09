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
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies');

            // Contract Information
            $table->string('contract_number')->unique();
            $table->string('contract_type')->default('permanent'); // permanent, temporary, probation
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);

            // Salary Information (using Money pattern from existing tables)
            $table->unsignedBigInteger('base_salary'); // Monthly base salary
            $table->unsignedBigInteger('hourly_rate')->nullable(); // For hourly employees
            $table->string('pay_frequency')->default('monthly'); // monthly, bi_weekly, weekly, hourly

            // Allowances and Benefits
            $table->unsignedBigInteger('housing_allowance')->default(0);
            $table->unsignedBigInteger('transport_allowance')->default(0);
            $table->unsignedBigInteger('meal_allowance')->default(0);
            $table->unsignedBigInteger('other_allowances')->default(0);

            // Working Hours
            $table->decimal('working_hours_per_week', 5, 2)->default(40.00);
            $table->decimal('working_days_per_week', 3, 1)->default(5.0);

            // Leave Entitlements
            $table->integer('annual_leave_days')->default(21);
            $table->integer('sick_leave_days')->default(10);
            $table->integer('maternity_leave_days')->default(90);
            $table->integer('paternity_leave_days')->default(7);

            // Probation Period
            $table->integer('probation_period_months')->nullable();
            $table->date('probation_end_date')->nullable();

            // Notice Period
            $table->integer('notice_period_days')->default(30);

            // Contract Terms
            $table->text('terms_and_conditions')->nullable();
            $table->text('job_description')->nullable();

            // Approval and Signing
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['employee_id']);
            $table->index(['contract_type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
