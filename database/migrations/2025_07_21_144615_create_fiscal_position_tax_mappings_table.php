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
        Schema::create('fiscal_position_tax_mappings', function (Blueprint $table) {
            $table->foreignId('fiscal_position_id')->constrained('fiscal_positions')->onDelete('cascade');
            $table->foreignId('original_tax_id')->constrained('taxes');
            $table->foreignId('mapped_tax_id')->constrained('taxes');
            $table->timestamps();

            $table->primary(['fiscal_position_id', 'original_tax_id'], 'fptm_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_position_tax_mappings');
    }
};
