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
        Schema::create('deferred_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('type'); // revenue, expense
            $table->string('name');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('deferred_amount', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('method')->default('linear');

            // Accounts
            $table->unsignedBigInteger('deferred_account_id');
            $table->unsignedBigInteger('recognition_account_id');

            // Source (Polymorphic)
            $table->nullableMorphs('source');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('deferred_account_id')->references('id')->on('accounts')->restrictOnDelete();
            $table->foreign('recognition_account_id')->references('id')->on('accounts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deferred_items');
    }
};
