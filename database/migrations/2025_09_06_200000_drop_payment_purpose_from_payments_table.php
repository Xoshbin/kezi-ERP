<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_purpose')) {
                $table->dropColumn('payment_purpose');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Recreate the column if rolling back, defaulting to 'settlement'
            if (! Schema::hasColumn('payments', 'payment_purpose')) {
                $table->string('payment_purpose')->default('settlement')->comment('The business purpose of the payment (e.g., settlement, loan).');
            }
        });
    }
};

