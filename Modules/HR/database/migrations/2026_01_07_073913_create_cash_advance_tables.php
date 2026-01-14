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
        // Cash Advances
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('advance_number')->unique();

            $table->unsignedBigInteger('requested_amount'); // Minor units
            $table->unsignedBigInteger('approved_amount')->nullable(); // Minor units
            $table->unsignedBigInteger('disbursed_amount')->nullable(); // Minor units

            $table->text('purpose');
            $table->date('expected_return_date')->nullable();

            $table->string('status')->default('draft')->index(); // draft, pending_approval, approved, disbursed, pending_settlement, settled, rejected, cancelled

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('disbursement_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('settlement_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'status']);
        });

        // Expense Reports
        Schema::create('expense_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_advance_id')->constrained('cash_advances')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('report_number')->unique();

            $table->date('report_date');
            $table->unsignedBigInteger('total_amount'); // Minor units, calculated from lines

            $table->string('status')->default('draft')->index(); // draft, submitted, approved, rejected

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'cash_advance_id']);
        });

        // Expense Report Lines
        Schema::create('expense_report_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_report_id')->constrained('expense_reports')->cascadeOnDelete();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();

            $table->text('description');
            $table->date('expense_date');
            $table->unsignedBigInteger('amount'); // Minor units
            $table->string('receipt_reference')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_report_lines');
        Schema::dropIfExists('expense_reports');
        Schema::dropIfExists('cash_advances');
    }
};
