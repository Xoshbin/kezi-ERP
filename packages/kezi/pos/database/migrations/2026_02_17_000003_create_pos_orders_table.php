<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pos_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();

            $table->string('order_number')->nullable();
            $table->string('status')->default('draft'); // draft, paid, posted, cancelled
            $table->timestamp('ordered_at');

            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('total_tax')->default(0);

            $table->json('sector_data')->nullable(); // Context specific (table_id, pump_id, etc.)
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['pos_session_id', 'status']);
        });

        Schema::create('pos_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->decimal('quantity', 16, 4);
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);

            $table->json('metadata')->nullable(); // variant choices, modifiers

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_lines');
        Schema::dropIfExists('pos_orders');
    }
};
