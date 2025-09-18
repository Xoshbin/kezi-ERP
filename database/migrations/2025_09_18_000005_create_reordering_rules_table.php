<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reordering_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->decimal('min_qty', 18, 6)->default(0);
            $table->decimal('max_qty', 18, 6)->default(0);
            $table->decimal('safety_stock', 18, 6)->default(0);
            $table->decimal('multiple', 18, 6)->default(1);
            $table->string('route'); // min_max, mto
            $table->integer('lead_time_days')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'product_id', 'location_id'], 'uniq_company_product_location_rule');
            $table->index(['company_id', 'active']);
            $table->index(['product_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reordering_rules');
    }
};
