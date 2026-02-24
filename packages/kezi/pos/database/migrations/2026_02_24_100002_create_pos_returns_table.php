<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Core relationships
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_order_id')
                ->constrained('pos_orders')
                ->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();

            // Return identification
            $table->string('return_number')->unique();
            $table->timestamp('return_date');

            // Status workflow
            $table->string('status')->default('draft');
            // draft, pending_approval, approved, rejected, processing, completed, cancelled

            // Return reason
            $table->string('return_reason')->nullable();
            $table->text('return_notes')->nullable();

            // User tracking
            $table->foreignId('requested_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Financial details
            $table->bigInteger('refund_amount')->default(0);
            $table->bigInteger('restocking_fee')->default(0);
            $table->string('refund_method')->nullable();
            // original_method, cash, store_credit, bank_transfer

            // Accounting integration
            $table->foreignId('credit_note_id')
                ->nullable()
                ->constrained('invoices') // Credit notes are stored in invoices table
                ->nullOnDelete();
            $table->foreignId('payment_reversal_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            // Inventory integration
            $table->foreignId('stock_move_id')
                ->nullable()
                ->constrained('stock_moves')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status', 'return_date']);
            $table->index(['pos_session_id', 'status']);
            $table->index(['original_order_id']);
            $table->index(['requested_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_returns');
    }
};
