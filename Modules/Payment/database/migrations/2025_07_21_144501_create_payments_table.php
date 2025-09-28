<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;

return new class() extends Migration {
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
            $table->foreignId('paid_to_from_partner_id')->nullable()->constrained('partners');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            // Add exchange rate captured at payment registration
            $table->decimal('exchange_rate_at_payment', 20, 10)->nullable();

            // Add company currency amount (converted amount)
            $table->unsignedBigInteger('amount_company_currency')->nullable();
            $table->date('payment_date');
            $table->unsignedBigInteger('amount');
            $table->string('payment_type'); // 'inbound', 'outbound'
            $table->string('reference')->nullable();
            $table->string('status')->default(PaymentStatus::Draft->value)->index(); // 'draft', 'confirmed', 'reconciled'
            $table->string('payment_method')
                ->default(PaymentMethod::Manual->value)
                ->comment('The method used for this payment (manual, check, bank_transfer, etc.)');
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
