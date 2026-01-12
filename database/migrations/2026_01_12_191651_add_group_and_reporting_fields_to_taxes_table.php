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
        Schema::table('taxes', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('type');
            $table->string('country')->nullable()->after('is_group'); // ISO country code
            $table->string('report_tag')->nullable()->after('country'); // e.g., 'VAT_SALES_15'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'country', 'report_tag']);
        });
    }
};
