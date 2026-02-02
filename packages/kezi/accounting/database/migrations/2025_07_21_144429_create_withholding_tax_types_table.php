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
        Schema::create('withholding_tax_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->json('name'); // Translatable
            $table->decimal('rate', 5, 4); // e.g., 0.0500 for 5%, 0.1500 for 15%
            $table->bigInteger('threshold_amount')->nullable(); // Minor units - minimum amount before WHT applies
            $table->string('applicable_to')->default('both'); // services, goods, both
            $table->foreignId('withholding_account_id')->constrained('accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for common queries
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_types');
    }
};
