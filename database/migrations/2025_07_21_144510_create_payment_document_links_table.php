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
        Schema::create('payment_document_links', function (Blueprint $table) {
            $table->id(); // A simple PK is easier than a complex composite key with nullables
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('cascade');
            $table->foreignId('vendor_bill_id')->nullable()->constrained('vendor_bills')->onDelete('cascade');
            $table->unsignedBigInteger('amount_applied');
            $table->timestamps();

            // Note: The constraint that either invoice_id or vendor_bill_id is present
            // should be handled by your application logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_document_links');
    }
};
