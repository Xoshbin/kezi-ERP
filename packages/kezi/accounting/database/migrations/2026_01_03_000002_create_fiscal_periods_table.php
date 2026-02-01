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
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "January 2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->string('state')->default('open'); // open, closed
            $table->timestamps();

            // Ensure unique periods within a fiscal year
            $table->unique(['fiscal_year_id', 'start_date']);
            $table->unique(['fiscal_year_id', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
