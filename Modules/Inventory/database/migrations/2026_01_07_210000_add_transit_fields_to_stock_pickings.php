<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add transit-related fields to stock_pickings for two-step inter-warehouse transfers.
     *
     * This supports the workflow: Source → Transit → Destination
     * - transit_location_id: The in-transit virtual location
     * - destination_location_id: The final destination location
     * - shipped_at/received_at: Timestamps for ship/receive actions
     * - shipped_by_user_id/received_by_user_id: Who performed the actions
     */
    public function up(): void
    {
        Schema::table('stock_pickings', function (Blueprint $table) {
            // Transit location for two-step transfers
            $table->foreignId('transit_location_id')
                ->nullable()
                ->after('partner_id')
                ->constrained('stock_locations')
                ->nullOnDelete();

            // Destination location (source is determined by stock moves)
            $table->foreignId('destination_location_id')
                ->nullable()
                ->after('transit_location_id')
                ->constrained('stock_locations')
                ->nullOnDelete();

            // Shipping step tracking
            $table->timestamp('shipped_at')->nullable()->after('completed_at');
            $table->foreignId('shipped_by_user_id')
                ->nullable()
                ->after('shipped_at')
                ->constrained('users')
                ->nullOnDelete();

            // Receiving step tracking
            $table->timestamp('received_at')->nullable()->after('shipped_by_user_id');
            $table->foreignId('received_by_user_id')
                ->nullable()
                ->after('received_at')
                ->constrained('users')
                ->nullOnDelete();

            // Index for querying in-transit transfers
            $table->index(['type', 'state', 'transit_location_id'], 'stock_pickings_transfer_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('stock_pickings', function (Blueprint $table) {
            $table->dropIndex('stock_pickings_transfer_lookup');
            $table->dropForeign(['transit_location_id']);
            $table->dropForeign(['destination_location_id']);
            $table->dropForeign(['shipped_by_user_id']);
            $table->dropForeign(['received_by_user_id']);
            $table->dropColumn([
                'transit_location_id',
                'destination_location_id',
                'shipped_at',
                'shipped_by_user_id',
                'received_at',
                'received_by_user_id',
            ]);
        });
    }
};
