<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // Composite index for company-scoped searches
            $table->index(['company_id', 'ordered_at'], 'idx_company_ordered_at');

            // Index for order number searches (already partially indexed via unique, but explicit is better)
            $table->index('order_number', 'idx_order_number');

            // Index for customer searches
            $table->index(['customer_id', 'ordered_at'], 'idx_customer_ordered_at');

            // Index for amount-based searches (for range queries)
            $table->index(['company_id', 'total_amount', 'ordered_at'], 'idx_company_amount_date');

            // Index for payment method filtering
            $table->index(['payment_method', 'ordered_at'], 'idx_payment_method_date');

            // Index for status filtering (essential for returns eligibility)
            $table->index(['status', 'ordered_at'], 'idx_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex('idx_company_ordered_at');
            $table->dropIndex('idx_order_number');
            $table->dropIndex('idx_customer_ordered_at');
            $table->dropIndex('idx_company_amount_date');
            $table->dropIndex('idx_payment_method_date');
            $table->dropIndex('idx_status_date');
        });
    }
};
