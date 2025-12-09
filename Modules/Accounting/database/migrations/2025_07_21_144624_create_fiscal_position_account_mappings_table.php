<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fiscal_position_account_mappings', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('fiscal_position_id')->constrained('fiscal_positions')->onDelete('cascade');
            $table->foreignId('original_account_id')->constrained('accounts');
            $table->foreignId('mapped_account_id')->constrained('accounts');
            $table->timestamps();

            $table->primary(['fiscal_position_id', 'original_account_id'], 'fpam_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_position_account_mappings');
    }
};
