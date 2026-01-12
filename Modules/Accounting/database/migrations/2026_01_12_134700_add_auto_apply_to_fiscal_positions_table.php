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
        Schema::table('fiscal_positions', function (Blueprint $table) {
            $table->boolean('auto_apply')->default(false)->after('country');
            $table->boolean('vat_required')->default(false)->after('auto_apply');
            $table->string('zip_from')->nullable()->after('vat_required');
            $table->string('zip_to')->nullable()->after('zip_from');
            // Assuming we might want more specific geographic mapping later,
            // but for now country is already there as a string.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_positions', function (Blueprint $table) {
            $table->dropColumn(['auto_apply', 'vat_required', 'zip_from', 'zip_to']);
        });
    }
};
