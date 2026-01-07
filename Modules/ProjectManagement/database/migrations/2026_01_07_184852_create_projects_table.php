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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('analytic_account_id')->nullable()->constrained('analytic_accounts')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, active, on_hold, completed, cancelled

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('hourly_rate', 15, 2)->nullable();

            $table->decimal('budget_amount', 19, 4)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->string('billing_type')->default('time_and_materials'); // fixed_price, time_and_materials, milestone

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['manager_id']);
            $table->index(['analytic_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
