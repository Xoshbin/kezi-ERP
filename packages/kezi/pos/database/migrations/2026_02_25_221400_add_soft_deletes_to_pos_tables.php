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
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pos_returns', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pos_returns', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
