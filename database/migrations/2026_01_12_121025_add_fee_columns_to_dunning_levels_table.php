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
        Schema::table('dunning_levels', function (Blueprint $table) {
            $table->boolean('charge_fee')->default(false);
            $table->decimal('fee_amount', 20, 4)->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->foreignId('fee_product_id')->nullable()->constrained('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_levels', function (Blueprint $table) {
            $table->dropForeign(['fee_product_id']);
            $table->dropColumn(['charge_fee', 'fee_amount', 'fee_percentage', 'fee_product_id']);
        });
    }
};
