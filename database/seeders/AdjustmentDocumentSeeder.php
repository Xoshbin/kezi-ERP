<?php

namespace Database\Seeders;

use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Journal;
use Brick\Money\Money;
use Illuminate\Database\Seeder;

class AdjustmentDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $currencyCode = $company->currency->code;

        // 1. Find the original invoice for "Hawre Trading Group"
        $originalInvoice = Invoice::whereHas('customer', function ($query) {
            $query->where('name', 'Hawre Trading Group');
        })->latest()->first();

        if (!$originalInvoice) {
            $this->command->error('The original invoice for "Hawre Trading Group" was not found. Please run the InvoiceSeeder.');
            return;
        }

        // 2. Find the "Sales" journal for creating the credit note
        $salesJournal = Journal::where('name->en', 'Sales')->where('company_id', $company->id)->first();
        if (!$salesJournal) {
            $this->command->error('The "Sales" journal was not found. Please run the JournalSeeder.');
            return;
        }

        // 3. Find the "Sales Discounts & Returns" account
        $salesDiscountAccount = Account::where('name->en', 'Sales Discounts & Returns')->where('company_id', $company->id)->first();
        if (!$salesDiscountAccount) {
            $this->command->error('The "Sales Discounts & Returns" account was not found. Please run the AccountSeeder.');
            return;
        }

        // 4. Create the Credit Note (Adjustment Document)
        $creditNoteAmount = Money::of('500000', $currencyCode);

        $creditNote = AdjustmentDocument::create([
            'company_id' => $company->id,
            'original_invoice_id' => $originalInvoice->id,
            'journal_id' => $salesJournal->id,
            'type' => AdjustmentDocumentType::CreditNote,
            'date' => now(),
            'reason' => 'Goodwill discount for new client.',
            'total_amount' => $creditNoteAmount,
            'status' => AdjustmentDocumentStatus::Draft,
            'currency_id' => $company->currency_id,
        ]);

        // 5. Create the line for the Credit Note
        $creditNote->lines()->create([
            'account_id' => $salesDiscountAccount->id,
            'description' => 'Refund for IT Setup Services',
            'quantity' => 1,
            'unit_price' => $creditNoteAmount,
        ]);

        // 6. Post the Credit Note (The observer will handle Journal Entry creation)
        // In a real app, this would be an action `PostAdjustmentDocumentAction->execute($creditNote)`
        $creditNote->update(['status' => AdjustmentDocumentStatus::Posted, 'posted_at' => now()]);

        $this->command->info('Credit note for Hawre Trading Group created successfully.');
    }
}
