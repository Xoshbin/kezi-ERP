<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_pickings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('type');
            $table->string('state');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->foreignId('transit_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('shipped_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('grn_number')->nullable();
            $table->string('origin')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            $table->index(['type', 'state']);
            $table->index('grn_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_pickings');
    }
};
