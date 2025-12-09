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
        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('customer_payment_term_id')
                ->nullable()
                ->after('payable_account_id')
                ->constrained('payment_terms')
                ->nullOnDelete()
                ->comment('Default payment terms when this partner is a customer');

            $table->foreignId('vendor_payment_term_id')
                ->nullable()
                ->after('customer_payment_term_id')
                ->constrained('payment_terms')
                ->nullOnDelete()
                ->comment('Default payment terms when this partner is a vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['customer_payment_term_id']);
            $table->dropColumn('customer_payment_term_id');

            $table->dropForeign(['vendor_payment_term_id']);
            $table->dropColumn('vendor_payment_term_id');
        });
    }
};
