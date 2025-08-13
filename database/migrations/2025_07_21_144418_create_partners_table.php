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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->string('name');
            $table->string('type'); // 'customer', 'vendor', 'both'
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->after('tax_id');
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts')->after('receivable_account_id');
            // This column links a partner record to a company record in our system.
            // It's nullable because not all partners are internal companies.
            $table->foreignId('linked_company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->onDelete('cascade'); // If a company is deleted, its partner link is also removed.
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
