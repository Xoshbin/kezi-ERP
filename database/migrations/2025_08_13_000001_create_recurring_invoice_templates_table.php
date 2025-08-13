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
        Schema::create('recurring_invoice_templates', function (Blueprint $table) {
            $table->id();
            
            // Company relationships
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('target_company_id')->constrained('companies')->onDelete('cascade');
            
            // Template identification
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('reference_prefix')->default('IC-RECURRING');
            
            // Scheduling configuration
            $table->enum('frequency', ['monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->integer('day_of_month')->default(1); // For monthly: which day of month
            $table->integer('month_of_quarter')->default(1); // For quarterly: which month of quarter
            
            // Template status
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->boolean('is_active')->default(true);
            
            // Financial configuration
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('income_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->foreignId('tax_id')->nullable()->constrained('taxes');
            
            // Template data (JSON storage for line items)
            $table->json('template_data'); // Stores line items and other template configuration
            
            // Audit fields
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->timestamp('last_generated_at')->nullable();
            $table->integer('generation_count')->default(0);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['next_run_date', 'status']);
            $table->index(['company_id', 'target_company_id']);
            
            // Ensure unique template names per company
            $table->unique(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_templates');
    }
};
