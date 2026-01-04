<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to support Goods Receipt Note (GRN) workflow:
     * - purchase_order_id: Links the receipt to a purchase order
     * - grn_number: Official GRN document number (assigned on validation)
     * - validated_at: When the receipt was validated
     * - validated_by_user_id: User who validated the receipt
     */
    public function up(): void
    {
        Schema::table('stock_pickings', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('partner_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();

            $table->string('grn_number')->nullable()->after('reference');
            $table->timestamp('validated_at')->nullable()->after('completed_at');
            $table->foreignId('validated_by_user_id')
                ->nullable()
                ->after('validated_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['type', 'state']);
            $table->index('grn_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_pickings', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['validated_by_user_id']);
            $table->dropIndex(['type', 'state']);
            $table->dropIndex(['grn_number']);
            $table->dropColumn([
                'purchase_order_id',
                'grn_number',
                'validated_at',
                'validated_by_user_id',
            ]);
        });
    }
};
