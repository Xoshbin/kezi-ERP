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
        Schema::create('payment_installment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('payment_installment_id')->constrained('payment_installments')->onDelete('cascade');
            $table->unsignedBigInteger('amount_applied')->comment('Amount of payment applied to this installment in minor currency units');
            $table->timestamps();

            $table->unique(['payment_id', 'payment_installment_id'], 'payment_installment_unique');
            $table->index(['payment_installment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_installment_links');
    }
};
