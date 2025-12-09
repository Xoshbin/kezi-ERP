<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Foundation\Enums\PaymentTerms\PaymentTermType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_term_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_term_id')->constrained('payment_terms')->onDelete('cascade');
            $table->unsignedTinyInteger('sequence')->default(1)->comment('Order of this line in the payment term');
            $table->string('type')->default(PaymentTermType::Net->value)->comment('Type of payment term calculation');
            $table->unsignedInteger('days')->default(0)->comment('Number of days for payment calculation');
            $table->decimal('percentage', 5, 2)->default(100.00)->comment('Percentage of total amount for this installment');
            $table->unsignedTinyInteger('day_of_month')->nullable()->comment('Specific day of month for DayOfMonth type');
            $table->decimal('discount_percentage', 5, 2)->nullable()->comment('Early payment discount percentage');
            $table->unsignedInteger('discount_days')->nullable()->comment('Days within which discount applies');
            $table->timestamps();

            $table->index(['payment_term_id', 'sequence']);
            $table->unique(['payment_term_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_term_lines');
    }
};
