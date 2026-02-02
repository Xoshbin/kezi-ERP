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
        Schema::create('lc_utilizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('letter_of_credit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_bill_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('utilized_amount');
            $table->unsignedBigInteger('utilized_amount_company_currency');
            $table->date('utilization_date');

            $table->timestamps();

            // Ensure a vendor bill can only be linked to one LC once
            $table->unique(['letter_of_credit_id', 'vendor_bill_id']);
            $table->index('letter_of_credit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_utilizations');
    }
};
