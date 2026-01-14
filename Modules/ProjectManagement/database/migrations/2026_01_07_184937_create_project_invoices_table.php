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
        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->date('invoice_date');
            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('labor_amount', 19, 4)->default(0);
            $table->decimal('expense_amount', 19, 4)->default(0);
            $table->decimal('total_amount', 19, 4);

            $table->string('status')->default('draft'); // draft, invoiced, cancelled

            $table->timestamps();

            // Indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
