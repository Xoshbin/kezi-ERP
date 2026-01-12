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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('dunning_level_id')->nullable()->constrained('dunning_levels')->nullOnDelete();
            $table->timestamp('last_dunning_date')->nullable();
            $table->date('next_dunning_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['dunning_level_id']);
            $table->dropColumn(['dunning_level_id', 'last_dunning_date', 'next_dunning_date']);
        });
    }
};
