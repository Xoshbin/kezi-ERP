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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts');
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('name');
            $table->date('purchase_date');
            $table->unsignedBigInteger('purchase_value');
            $table->unsignedBigInteger('salvage_value')->default(0);
            $table->unsignedInteger('useful_life_years');
            $table->string('depreciation_method');
            $table->string('status')->default('Draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
