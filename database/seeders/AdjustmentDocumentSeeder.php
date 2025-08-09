<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AdjustmentDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch the "Adjustments" journal
        $adjustmentJournal = Journal::where('name->en', 'Adjustments')->first();

        if (!$adjustmentJournal) {
            $this->command->error('The "Adjustments" journal was not found. Please run the JournalSeeder.');
            return;
        }

        $accounts = Account::all();

        if ($accounts->count() < 2) {
            $this->command->error('Not enough accounts found. Please run the AccountSeeder.');
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $amount = rand(10000, 100000) / 100; // Random amount between 100.00 and 1000.00
            $isDebit = (bool)rand(0, 1);

            // Get two random distinct accounts
            $randomAccounts = $accounts->random(2);
            $adjustmentAccount = $randomAccounts->first();
            $offsettingAccount = $randomAccounts->last();

            // Create the adjustment document
            $document = AdjustmentDocument::create([
                'company_id' => 1, // Assuming company_id 1 exists
                'journal_id' => $adjustmentJournal->id,
                'reference' => 'ADJ-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'date' => Carbon::now(),
                'posting_date' => Carbon::now(),
                'status' => 'posted',
                'notes' => 'Sample adjustment for various reasons.',
                'total_amount' => $amount,
            ]);

            // Create the corresponding journal entry
            $journalEntry = JournalEntry::create([
                'company_id' => 1,
                'journal_id' => $adjustmentJournal->id,
                'date' => $document->posting_date,
                'reference' => $document->reference,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => 'posted',
                'documentable_id' => $document->id,
                'documentable_type' => AdjustmentDocument::class,
            ]);

            // Create journal entry lines
            $journalEntry->lines()->create([
                'account_id' => $adjustmentAccount->id,
                'debit' => $isDebit ? $amount : 0,
                'credit' => !$isDebit ? $amount : 0,
                'label' => 'Adjustment Entry',
            ]);

            $journalEntry->lines()->create([
                'account_id' => $offsettingAccount->id,
                'debit' => !$isDebit ? $amount : 0,
                'credit' => $isDebit ? $amount : 0,
                'label' => 'Offsetting Entry for Adjustment',
            ]);
        }
    }
}
