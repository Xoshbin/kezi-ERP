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
        Schema::table('chequebooks', function (Blueprint $table) {
            $table->string('bank_name')->after('name')->nullable();
            $table->string('bank_account_number')->after('bank_name')->nullable();
            $table->unsignedInteger('digits')->after('prefix')->default(6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chequebooks', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'digits']);
        });
    }
};
