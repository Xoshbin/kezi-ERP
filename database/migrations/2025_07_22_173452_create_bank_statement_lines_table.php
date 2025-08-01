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
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->bigInteger('amount');
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->after('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
