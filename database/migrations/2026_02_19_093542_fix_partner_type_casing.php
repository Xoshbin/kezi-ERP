<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('partners')->update([
            'type' => \Illuminate\Support\Facades\DB::raw('LOWER(type)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to restore original casing if it was mixed,
        // but typically business modules capitalize these values in UI.
        // However, the system relies on lowercase for enums.
    }
};
