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
        Schema::table('analytic_plans', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('company_id')
                ->constrained('analytic_plans')
                ->nullOnDelete();
            $table->string('color')->nullable()->after('name');
            $table->string('default_applicability')->default('optional')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytic_plans', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'color', 'default_applicability']);
        });
    }
};
