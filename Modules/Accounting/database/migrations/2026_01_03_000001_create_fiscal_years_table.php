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
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "FY 2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->string('state')->default('open'); // draft, open, closing, closed
            $table->foreignId('closing_journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();
            $table->foreignId('closed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Ensure no overlapping fiscal years per company
            $table->unique(['company_id', 'start_date']);
            $table->unique(['company_id', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
