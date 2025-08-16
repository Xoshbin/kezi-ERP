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
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->foreignId('original_currency_id')
                  ->nullable()
                  ->after('original_currency_amount')
                  ->constrained('currencies')
                  ->comment('Currency of the original transaction amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropForeign(['original_currency_id']);
            $table->dropColumn('original_currency_id');
        });
    }
};
