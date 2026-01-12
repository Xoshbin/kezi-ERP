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
        Schema::create('deferred_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('deferred_item_id');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('status')->default('draft'); // draft, posted, cancelled
            $table->timestamps();

            $table->foreign('deferred_item_id')->references('id')->on('deferred_items')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deferred_lines');
    }
};
