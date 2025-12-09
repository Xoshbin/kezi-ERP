<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loan_agreements')->onDelete('cascade');
            $table->unsignedInteger('sequence');
            $table->date('due_date');
            $table->unsignedBigInteger('payment_amount')->default(0);
            $table->unsignedBigInteger('principal_component')->default(0);
            $table->unsignedBigInteger('interest_component')->default(0);
            $table->unsignedBigInteger('outstanding_balance_after')->default(0);
            $table->boolean('is_accrual_posted')->default(false);
            $table->boolean('is_payment_posted')->default(false);
            $table->foreignId('journal_entry_id_accrual')->nullable()->constrained('journal_entries');
            $table->foreignId('journal_entry_id_payment')->nullable()->constrained('journal_entries');
            $table->timestamps();

            $table->unique(['loan_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedule_entries');
    }
};
