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
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('prorata_temporis')->default(false)->after('status');
            $table->double('declining_factor')->nullable()->after('prorata_temporis');
        });

        Schema::table('asset_categories', function (Blueprint $table) {
            $table->boolean('prorata_temporis')->default(false)->after('name');
            $table->double('declining_factor')->nullable()->after('prorata_temporis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['prorata_temporis', 'declining_factor']);
        });

        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn(['prorata_temporis', 'declining_factor']);
        });
    }
};
