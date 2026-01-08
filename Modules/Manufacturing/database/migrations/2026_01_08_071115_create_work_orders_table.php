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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->foreignId('work_center_id')->constrained('work_centers')->restrictOnDelete();
            $table->integer('sequence')->default(1); // Order of operations (for future multi-operation support)
            $table->string('name');
            $table->string('status')->default('pending'); // pending, ready, in_progress, done, cancelled
            $table->decimal('planned_duration', 10, 2)->nullable(); // Planned hours
            $table->decimal('actual_duration', 10, 2)->nullable(); // Actual hours
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'manufacturing_order_id']);
            $table->index(['company_id', 'work_center_id']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
