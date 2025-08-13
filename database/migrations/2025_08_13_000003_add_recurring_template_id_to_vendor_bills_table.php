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
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->foreignId('recurring_template_id')
                ->nullable()
                ->after('journal_entry_id')
                ->constrained('recurring_invoice_templates')
                ->onDelete('set null');
            
            $table->index(['recurring_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropForeign(['recurring_template_id']);
            $table->dropColumn('recurring_template_id');
        });
    }
};
