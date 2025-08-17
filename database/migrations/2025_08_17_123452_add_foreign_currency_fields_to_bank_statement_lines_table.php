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
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            // Optional fields for foreign currency transactions.
            $table->foreignId('foreign_currency_id')->nullable()->constrained('currencies')
                ->comment('The original currency of the transaction if different from statement currency');
            $table->bigInteger('amount_in_foreign_currency')->nullable()
                ->comment('The original transaction amount in the foreign currency (minor units)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['foreign_currency_id']);
            $table->dropColumn(['foreign_currency_id', 'amount_in_foreign_currency']);
        });
    }
};
