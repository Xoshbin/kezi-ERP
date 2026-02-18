<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_profiles', function (Blueprint $table) {
            $table->foreignId('default_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payment_journal_id')->nullable()->constrained('journals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_income_account_id');
            $table->dropConstrainedForeignId('default_payment_journal_id');
        });
    }
};
