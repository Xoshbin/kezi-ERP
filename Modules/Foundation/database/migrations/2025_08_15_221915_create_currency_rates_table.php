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
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 20, 10); // High precision for exchange rates
            $table->date('effective_date'); // Date when this rate became effective
            $table->string('source')->nullable(); // Source of the rate (manual, API, etc.)
            $table->timestamps();

            // Ensure unique rate per company per currency per date
            $table->unique(['company_id', 'currency_id', 'effective_date'], 'currency_rates_company_currency_date_unique');

            // Index for efficient rate lookups
            $table->index(['currency_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
