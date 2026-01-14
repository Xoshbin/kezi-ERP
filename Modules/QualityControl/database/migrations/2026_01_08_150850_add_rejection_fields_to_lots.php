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
        Schema::table('lots', function (Blueprint $table) {
            $table->boolean('is_rejected')->default(false)->after('active');
            $table->text('rejection_reason')->nullable()->after('is_rejected');
            $table->foreignId('quarantine_location_id')->nullable()->after('rejection_reason')->constrained('stock_locations', 'id')->nullOnDelete();
            $table->index(['is_rejected', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['quarantine_location_id']);
            $table->dropIndex(['is_rejected', 'active']);
            $table->dropColumn(['is_rejected', 'rejection_reason', 'quarantine_location_id']);
        });
    }
};
