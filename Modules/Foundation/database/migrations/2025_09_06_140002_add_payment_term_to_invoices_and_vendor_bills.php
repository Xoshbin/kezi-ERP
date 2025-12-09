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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('payment_term_id')
                ->nullable()
                ->after('due_date')
                ->constrained('payment_terms')
                ->nullOnDelete()
                ->comment('Payment terms for this invoice');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->foreignId('payment_term_id')
                ->nullable()
                ->after('due_date')
                ->constrained('payment_terms')
                ->nullOnDelete()
                ->comment('Payment terms for this vendor bill');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            $table->dropColumn('payment_term_id');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            $table->dropColumn('payment_term_id');
        });
    }
};
