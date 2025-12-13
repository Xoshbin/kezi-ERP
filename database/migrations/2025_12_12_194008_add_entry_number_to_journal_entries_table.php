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
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'entry_number')) {
                $table->string('entry_number')->nullable()->after('journal_id');
            }
            // Add new unique constraint involving entry_number FIRST to satisfy FK on company_id
            $table->unique(['company_id', 'journal_id', 'entry_number']);
            // Drop the old unique constraint involving reference
            $table->dropUnique(['company_id', 'journal_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'journal_id', 'entry_number']);
            $table->unique(['company_id', 'journal_id', 'reference']);
            $table->dropColumn('entry_number');
        });
    }
};
