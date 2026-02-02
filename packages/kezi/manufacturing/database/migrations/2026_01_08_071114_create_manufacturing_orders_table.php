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
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique(); // MO number from sequence
            $table->foreignId('bom_id')->constrained('bills_of_materials')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_to_produce', 15, 4);
            $table->decimal('quantity_produced', 15, 4)->default(0);
            $table->string('status')->default('draft'); // draft, confirmed, in_progress, done, cancelled
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->dateTime('actual_start_date')->nullable();
            $table->dateTime('actual_end_date')->nullable();
            $table->foreignId('source_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'planned_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_orders');
    }
};
