<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to support three-way matching (PO ↔ GRN ↔ Bill):
     * - stock_picking_id: Links the bill to the Goods Receipt that received the goods
     * - three_way_match_status: Indicates the current matching status
     */
    public function up(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->foreignId('stock_picking_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('stock_pickings')
                ->nullOnDelete();

            $table->string('three_way_match_status')
                ->nullable()
                ->after('status')
                ->default(null);

            $table->index('three_way_match_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropForeign(['stock_picking_id']);
            $table->dropIndex(['three_way_match_status']);
            $table->dropColumn(['stock_picking_id', 'three_way_match_status']);
        });
    }
};
