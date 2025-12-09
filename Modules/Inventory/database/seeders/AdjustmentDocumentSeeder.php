<?php

namespace Modules\Inventory\Database\Seeders;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Account;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Sales\Models\Invoice;

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

        if (! $originalInvoice) {
            $this->command->error('The original invoice for "Hawre Trading Group" was not found. Please run the InvoiceSeeder.');

            return;
        }

        // Note: Journal entry will be created when the document is posted

        // 3. Find the "Sales Discounts & Returns" account
        $salesDiscountAccount = Account::where('name->en', 'Sales Discounts & Returns')->where('company_id', $company->id)->first();
        if (! $salesDiscountAccount) {
            $this->command->error('The "Sales Discounts & Returns" account was not found. Please run the AccountSeeder.');

            return;
        }

        // 4. Create the Credit Note (Adjustment Document)
        $creditNoteAmount = Money::of('500000', $currencyCode);
        $zeroAmount = Money::of('0', $currencyCode);

        $creditNote = AdjustmentDocument::create([
            'company_id' => $company->id,
            'original_invoice_id' => $originalInvoice->id,
            'type' => AdjustmentDocumentType::CreditNote,
            'date' => now(),
            'reference_number' => 'CN-'.now()->format('Ymd').'-001',
            'reason' => 'Goodwill discount for new client.',
            'subtotal' => $creditNoteAmount,
            'total_amount' => $creditNoteAmount,
            'total_tax' => $zeroAmount,
            'status' => AdjustmentDocumentStatus::Draft,
            'currency_id' => $company->currency_id,
        ]);

        // 5. Create the line for the Credit Note
        $creditNote->lines()->create([
            'company_id' => $company->id,
            'account_id' => $salesDiscountAccount->id,
            'description' => 'Refund for IT Setup Services',
            'quantity' => 1,
            'unit_price' => $creditNoteAmount,
        ]);

        // 6. Post the Credit Note (The AdjustmentDocumentService will handle Journal Entry creation)
        // In a real app, this would be an action `PostAdjustmentDocumentAction->execute($creditNote)`
        $creditNote->update(['status' => AdjustmentDocumentStatus::Posted, 'posted_at' => now()]);

        $this->command->info('Credit note for Hawre Trading Group created successfully.');
    }
}
