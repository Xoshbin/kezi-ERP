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
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->foreignId('analytic_account_id')->nullable()->constrained('analytic_accounts');
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->unsignedBigInteger('budgeted_amount');
            $table->unsignedBigInteger('achieved_amount')->default(0);
            $table->unsignedBigInteger('committed_amount')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
