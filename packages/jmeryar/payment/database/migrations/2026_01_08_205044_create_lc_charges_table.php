<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Payment\Enums\LetterOfCredit\LCChargeType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lc_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('letter_of_credit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();

            $table->string('charge_type')->default(LCChargeType::Other->value);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_company_currency');
            $table->date('charge_date');
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['letter_of_credit_id', 'charge_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_charges');
    }
};
