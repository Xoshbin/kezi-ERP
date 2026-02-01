<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;

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
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('total_debit');
            $table->unsignedBigInteger('total_credit');

            // Add index for performance on currency queries
            $table->index(['currency_id', 'entry_date']);
            /*
            ## 1. Performance 🚀
            A query on a boolean/tinyint column like WHERE is_posted = true is extremely fast, especially with a database index.
            is_posted (a boolean): Answers a simple, fundamental question: "Is this an immutable, official part of the general ledger?"
            The answer is a definitive yes or no. Your code becomes very readable: if ($journalEntry->is_posted) { ... }.
             */
            $table->boolean('is_posted')->default(false)->index();
            /*
            ## 1. Performance 🚀
            A query on a string column like WHERE state = 'posted' is slightly less performant.
            ## 2. Logical Clarity and Code Readability
            state (a string): Describes the record's position in a workflow. A record can be a draft,
            then become posted, and later could be reversed.
            This distinction is important. For example, a reversed entry is no longer active, but it was posted. It is still an immutable part of the audit trail. In this scenario:

            is_posted would remain true.

            state would be reversed.
             */
            $table->string('state')->default(JournalEntryState::Posted)->index(); // Add this: draft, posted, reversed
            $table->foreignId('reversed_entry_id')->nullable()->constrained('journal_entries');
            $table->string('hash', 64)->nullable()->index();
            $table->string('previous_hash', 64)->nullable()->index();
            $table->nullableMorphs('source'); // source_id and source_type
            $table->string('entry_number')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'journal_id', 'entry_number']);
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
