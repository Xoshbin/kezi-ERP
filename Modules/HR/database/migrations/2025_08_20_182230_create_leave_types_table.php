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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->json('name'); // Translatable field
            $table->string('code')->unique(); // annual, sick, maternity, paternity, emergency, etc.
            $table->text('description')->nullable();
            $table->integer('default_days_per_year')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->boolean('carries_forward')->default(false); // Can unused days carry to next year
            $table->integer('max_carry_forward_days')->default(0);
            $table->integer('max_consecutive_days')->nullable(); // Max consecutive days allowed
            $table->integer('min_notice_days')->default(1); // Minimum notice required
            $table->boolean('requires_documentation')->default(false); // Medical certificate, etc.
            $table->string('color')->default('#3B82F6'); // For calendar display
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
