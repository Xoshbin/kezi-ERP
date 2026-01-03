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
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Document details
            $table->string('rfq_number')->unique();
            $table->date('rfq_date');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();

            // State management
            $table->string('status')->default('draft'); // draft, sent, bid_received, accepted, rejected, cancelled

            // Conversion tracking
            $table->foreignId('converted_to_purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            // Totals (minor units)
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('total')->default(0);

            // Exchange rate
            $table->decimal('exchange_rate', 18, 8)->default(1.0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_quotations');
    }
};
