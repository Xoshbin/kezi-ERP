<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('loan_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->string('name')->nullable();
            $table->date('loan_date');
            $table->date('start_date');
            $table->date('maturity_date')->nullable();
            $table->unsignedInteger('duration_months');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedBigInteger('principal_amount');
            $table->unsignedBigInteger('outstanding_principal')->default(0);
            $table->string('loan_type');
            $table->string('status')->default('draft');
            $table->string('schedule_method');
            $table->float('interest_rate')->default(0); // annual %
            $table->boolean('eir_enabled')->default(false);
            $table->float('eir_rate')->nullable(); // periodic rate if enabled
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_agreements');
    }
};
