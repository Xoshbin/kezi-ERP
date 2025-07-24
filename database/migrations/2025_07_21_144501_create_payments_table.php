<?php

use App\Models\Payment;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('journal_id')->constrained('journals');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('paid_to_from_partner_id')->constrained('partners');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_type'); // 'inbound', 'outbound'
            $table->string('reference')->nullable();
            $table->string('status')->default(Payment::STATUS_DRAFT)->index(); // 'draft', 'confirmed', 'reconciled'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
