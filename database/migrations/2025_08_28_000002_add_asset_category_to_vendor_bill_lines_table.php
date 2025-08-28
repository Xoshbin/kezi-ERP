<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_bill_lines', function (Blueprint $table) {
            $table->foreignId('asset_category_id')->nullable()->after('expense_account_id')->constrained('asset_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_bill_lines', function (Blueprint $table) {
            $table->dropForeign(['asset_category_id']);
            $table->dropColumn('asset_category_id');
        });
    }
};

