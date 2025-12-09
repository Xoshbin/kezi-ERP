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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->json('name');
            $table->string('type'); // e.g., 'sale', 'purchase', 'bank', 'cash', 'miscellaneous'
            $table->string('short_code');
            $table->foreignId('currency_id')->nullable()->constrained('currencies');
            $table->foreignId('default_debit_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_credit_account_id')->nullable()->constrained('accounts');
            // Add exchange gain and loss account configurations
            $table->foreignId('exchange_gain_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('exchange_loss_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('exchange_difference_journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamps();

            $table->unique(['company_id', 'short_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
