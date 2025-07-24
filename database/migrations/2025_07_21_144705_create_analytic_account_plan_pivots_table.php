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
        Schema::create('analytic_account_plan_pivots', function (Blueprint $table) {
            $table->foreignId('analytic_account_id')->constrained('analytic_accounts')->onDelete('cascade');
            $table->foreignId('analytic_plan_id')->constrained('analytic_plans')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['analytic_account_id', 'analytic_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytic_account_plan_pivots');
    }
};
