<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_profiles', function (Blueprint $table) {
            // Return policy settings (stored as JSON for flexibility)
            $table->json('return_policy')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('pos_profiles', function (Blueprint $table) {
            $table->dropColumn('return_policy');
        });
    }
};
