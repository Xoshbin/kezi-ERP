<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('serial_code');
            $table->string('status')->default('available');
            $table->foreignId('current_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('sold_to_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            // SAP-like: unique per product per company
            $table->unique(['company_id', 'product_id', 'serial_code'], 'uniq_company_product_serial');
            $table->index(['product_id', 'status']);
            $table->index(['company_id', 'current_location_id', 'status']);
            $table->index(['warranty_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
