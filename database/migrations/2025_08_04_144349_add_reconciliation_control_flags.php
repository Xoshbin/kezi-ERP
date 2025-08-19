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
        // Add company-level reconciliation control
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('enable_reconciliation')
                ->default(false)
                ->after('fiscal_country')
                ->comment('Global switch to enable/disable all reconciliation functionality for this company');
            
            // Add index for performance when checking this setting
            $table->index('enable_reconciliation');
        });

        // Add account-level reconciliation control
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('allow_reconciliation')
                ->default(false)
                ->after('is_deprecated')
                ->comment('Whether this account can be used in reconciliation processes (A/R, A/P, Bank)');
            
            // Add index for performance when filtering reconcilable accounts
            $table->index(['company_id', 'allow_reconciliation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['enable_reconciliation']);
            $table->dropColumn('enable_reconciliation');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'allow_reconciliation']);
            $table->dropColumn('allow_reconciliation');
        });
    }
};
