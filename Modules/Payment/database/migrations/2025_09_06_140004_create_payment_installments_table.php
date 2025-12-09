<?php


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Payment\Enums\PaymentInstallments\InstallmentStatus;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('installment_type')->comment('Polymorphic type (Invoice, VendorBill)');
            $table->unsignedBigInteger('installment_id')->comment('Polymorphic ID');
            $table->unsignedTinyInteger('sequence')->comment('Installment sequence number');
            $table->date('due_date')->comment('When this installment is due');
            $table->unsignedBigInteger('amount')->comment('Installment amount in minor currency units');
            $table->unsignedBigInteger('paid_amount')->default(0)->comment('Amount already paid in minor currency units');
            $table->string('status')->default(InstallmentStatus::Pending->value)->comment('Payment status of this installment');
            $table->decimal('discount_percentage', 5, 2)->nullable()->comment('Early payment discount percentage');
            $table->date('discount_deadline')->nullable()->comment('Deadline for early payment discount');
            $table->timestamps();

            $table->index(['installment_type', 'installment_id']);
            $table->index(['company_id', 'due_date']);
            $table->index(['company_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
