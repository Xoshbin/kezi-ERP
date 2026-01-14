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
        Schema::create('dunning_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('days_overdue')->default(0);
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->boolean('print_letter')->default(false);
            $table->boolean('send_email')->default(true);
            $table->boolean('charge_fee')->default(false);
            $table->decimal('fee_amount', 20, 4)->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->foreignId('fee_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dunning_levels');
    }
};
