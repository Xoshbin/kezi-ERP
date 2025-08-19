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
        // Create reconciliations table for tracking reconciliation records
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->enum('reconciliation_type', ['manual_ar_ap', 'bank_statement', 'manual_general'])
                ->comment('Type of reconciliation: manual A/R-A/P, bank statement, or general manual');
            $table->foreignId('reconciled_by_user_id')->constrained('users');
            $table->timestamp('reconciled_at');
            $table->string('reference')->nullable()
                ->comment('Optional reference number for the reconciliation');
            $table->text('description')->nullable()
                ->comment('Optional description or notes about the reconciliation');
            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'reconciliation_type']);
            $table->index(['reconciled_by_user_id', 'reconciled_at']);
            $table->index('reference');
        });

        // Create pivot table for linking journal entry lines to reconciliations
        Schema::create('journal_entry_line_reconciliation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_line_id')->constrained('journal_entry_lines')->onDelete('cascade');
            $table->foreignId('reconciliation_id')->constrained('reconciliations')->onDelete('cascade');
            $table->timestamps();

            // Ensure a journal entry line can only be in one reconciliation
            $table->unique('journal_entry_line_id', 'unique_line_reconciliation');
            
            // Index for efficient lookups
            $table->index('reconciliation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_line_reconciliation');
        Schema::dropIfExists('reconciliations');
    }
};
