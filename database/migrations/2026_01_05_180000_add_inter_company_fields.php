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
        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('linked_company_id')
                ->nullable()
                ->comment('Links this partner to a company for inter-company transactions')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('consolidation_method')
                ->default('full')
                ->comment('Consolidation method: full, proportional, equity');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('inter_company_source_id')->nullable();
            $table->string('inter_company_source_type')->nullable();

            // Explicit short index name to avoid length limit
            $table->index(['inter_company_source_id', 'inter_company_source_type'], 'inv_ic_source_idx');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('inter_company_source_id')->nullable();
            $table->string('inter_company_source_type')->nullable();

            // Explicit short index name to avoid length limit
            $table->index(['inter_company_source_id', 'inter_company_source_type'], 'vb_ic_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['linked_company_id']);
            $table->dropColumn('linked_company_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('consolidation_method');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('inv_ic_source_idx');
            $table->dropColumn(['inter_company_source_id', 'inter_company_source_type']);
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropIndex('vb_ic_source_idx');
            $table->dropColumn(['inter_company_source_id', 'inter_company_source_type']);
        });
    }
};
