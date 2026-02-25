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
        Schema::create('pos_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')
                ->constrained('pos_orders')
                ->cascadeOnDelete();
            $table->string('payment_method'); // e.g. 'cash', 'credit_card'
            $table->bigInteger('amount'); // Minor units
            $table->bigInteger('amount_tendered')->nullable(); // Minor units, only for cash
            $table->bigInteger('change_given')->default(0); // Minor units, only for cash
            $table->timestamps();

            $table->index('pos_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_payments');
    }
};
