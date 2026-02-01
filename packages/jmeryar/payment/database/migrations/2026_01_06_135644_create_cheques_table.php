<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Enums\Cheques\ChequeType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chequebook_id')->nullable()->constrained('chequebooks')->nullOnDelete();
            $table->foreignId('journal_id')->constrained('journals')->restrictOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->string('cheque_number');
            $table->unsignedBigInteger('amount'); // Minor units
            $table->unsignedBigInteger('amount_company_currency'); // Minor units (base)

            $table->date('issue_date');
            $table->date('due_date');

            $table->string('status')->default(ChequeStatus::Draft->value)->index();
            $table->string('type')->default(ChequeType::Payable->value)->index();

            $table->string('payee_name');
            $table->string('bank_name')->nullable(); // For receivable cheques
            $table->text('memo')->nullable();

            $table->datetime('deposited_at')->nullable();
            $table->datetime('cleared_at')->nullable();
            $table->datetime('bounced_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
