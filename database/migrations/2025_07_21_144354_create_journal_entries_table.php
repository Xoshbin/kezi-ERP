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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('journal_id')->constrained('journals');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->date('entry_date');
            $table->string('reference');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->boolean('is_posted')->default(false)->index();
            $table->string('hash', 64)->nullable()->index();
            $table->string('previous_hash', 64)->nullable()->index();
            $table->nullableMorphs('source'); // source_id and source_type
            $table->timestamps();

            $table->unique(['company_id', 'journal_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
