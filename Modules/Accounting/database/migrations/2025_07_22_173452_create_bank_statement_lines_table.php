<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->bigInteger('amount');
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies');
            // Optional fields for foreign currency transactions.
            $table->foreignId('foreign_currency_id')->nullable()->constrained('currencies')
                ->comment('The original currency of the transaction if different from statement currency');
            $table->bigInteger('amount_in_foreign_currency')->nullable()
                ->comment('The original transaction amount in the foreign currency (minor units)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
