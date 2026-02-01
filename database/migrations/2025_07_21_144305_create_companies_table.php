<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Inventory\Enums\Inventory\InventoryAccountingMode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('currency_id');
            $table->string('fiscal_country'); // e.g., 'IQ'
            $table->foreignId('parent_company_id')->nullable()->constrained('companies')->onDelete('set null');

            // Consolidation
            $table->string('consolidation_method')->default('full')->comment('Consolidation method: full, proportional, equity');

            $table->string('inventory_accounting_mode')
                ->default(InventoryAccountingMode::AUTO_RECORD_ON_BILL->value)
                ->comment('Controls how inventory journal entries are created when vendor bills are confirmed');
            $table->json('numbering_settings')->nullable();
            // PDF Template Settings
            $table->string('pdf_template', 50)->default('classic');
            $table->string('pdf_logo_path')->nullable();
            $table->json('pdf_settings')->nullable();

            $table->boolean('enable_reconciliation')
                ->default(false)
                ->comment('Global switch to enable/disable all reconciliation functionality for this company');

            // Add index for performance when checking this setting
            $table->index('enable_reconciliation');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
