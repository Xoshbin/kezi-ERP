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
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('document_type'); // 'invoice', 'vendor_bill', 'payment', etc.
            $table->string('prefix'); // 'INV', 'BILL', 'PAY', etc.
            $table->unsignedInteger('current_number')->default(0);
            $table->unsignedInteger('padding')->default(5); // Number of digits for padding (e.g., 5 for 00001)
            $table->timestamps();

            // Ensure one sequence per company per document type
            $table->unique(['company_id', 'document_type']);

            // Index for performance
            $table->index(['company_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
