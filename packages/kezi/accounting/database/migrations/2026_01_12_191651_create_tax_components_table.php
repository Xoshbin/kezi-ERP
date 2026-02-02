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
        Schema::create('tax_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->foreignId('child_tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['parent_tax_id', 'child_tax_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_components');
    }
};
