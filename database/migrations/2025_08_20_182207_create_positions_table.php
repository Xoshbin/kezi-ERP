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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->json('title'); // Translatable field
            $table->text('description')->nullable();
            $table->json('requirements')->nullable(); // Job requirements as JSON
            $table->json('responsibilities')->nullable(); // Job responsibilities as JSON
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract, intern
            $table->string('level')->default('entry'); // entry, junior, mid, senior, lead, manager, director
            $table->unsignedBigInteger('min_salary')->nullable(); // Money field
            $table->unsignedBigInteger('max_salary')->nullable(); // Money field
            $table->foreignId('salary_currency_id')->nullable()->constrained('currencies');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['department_id']);
            $table->index(['employment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
