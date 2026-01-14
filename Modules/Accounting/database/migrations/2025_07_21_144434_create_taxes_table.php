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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('tax_account_id')->constrained('accounts');
            $table->json('name');
            $table->decimal('rate', 10, 5); // e.g., 0.15000 for 15%
            $table->string('type'); // 'sale', 'purchase', 'none'
            $table->boolean('is_group')->default(false);
            $table->string('country')->nullable();
            $table->string('report_tag')->nullable();
            $table->string('computation')->default('percent'); // 'percent', 'percent_of_price_included', 'fixed', 'group'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recoverable')->default(true); // Whether tax can be deducted as input tax or should be capitalized
            $table->json('label_on_invoices')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
