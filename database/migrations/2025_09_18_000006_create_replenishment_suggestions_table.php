<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replenishment_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreignId('reordering_rule_id')->constrained()->cascadeOnDelete();
            $table->decimal('suggested_qty', 18, 6);
            $table->string('priority'); // normal, high, urgent
            $table->string('route'); // min_max, mto
            $table->text('reason');
            $table->string('origin_reference')->nullable();
            $table->date('suggested_order_date');
            $table->date('expected_delivery_date');
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'processed']);
            $table->index(['reordering_rule_id', 'processed']);
            $table->index(['priority', 'suggested_order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_suggestions');
    }
};
