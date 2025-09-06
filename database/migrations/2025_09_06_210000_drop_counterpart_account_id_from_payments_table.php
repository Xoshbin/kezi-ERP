<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'counterpart_account_id')) {
                $table->dropConstrainedForeignId('counterpart_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'counterpart_account_id')) {
                $table->foreignId('counterpart_account_id')
                    ->nullable()
                    ->constrained('accounts')
                    ->nullOnDelete()
                    ->comment('Deprecated: Do not use. Use Bank Statements/Misc Journal for non-AR/AP.');
            }
        });
    }
};

