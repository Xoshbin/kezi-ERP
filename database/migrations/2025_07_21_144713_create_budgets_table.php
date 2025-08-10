<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Budgets\BudgetStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('name');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('budget_type'); // 'analytic', 'financial'
            $table->string('status')->default(BudgetStatus::Draft->value); // 'draft', 'finalized'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
