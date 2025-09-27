<?php

namespace Modules\Accounting\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\AdjustmentDocument;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use RuntimeException;

class CreateJournalEntryForAdjustmentAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(\Modules\Inventory\Models\AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $adjustment->load('company.currency', 'currency');
        $company = $adjustment->company;
        $currencyCode = $adjustment->currency->code;

        // 2. Get the required default accounts from the company.
        $arAccountId = $company->default_accounts_receivable_id;
        $salesDiscountAccountId = $company->default_sales_discount_account_id;
        $taxAccountId = $company->default_tax_account_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (! $arAccountId || ! $salesDiscountAccountId || ! $taxAccountId || ! $salesJournalId) {
            throw new RuntimeException('Default accounting accounts for adjustments are not configured for this company.');
        }

        // 3. Build the journal entry lines based on credit note accounting rules (reversing a sale).
        $lineDTOs = [];
        // Use the properly cast Money objects from the adjustment document
        $totalAmount = $adjustment->total_amount;
        $totalTax = $adjustment->total_tax;
        $subtotal = $totalAmount->minus($totalTax);

        // Rule: DEBIT the Sales Discount/Contra-Revenue account.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $salesDiscountAccountId,
            debit: $subtotal,
            credit: Money::of(0, $currencyCode),
            description: 'Sales Discount/Contra-Revenue',
            partner_id: null,
            analytic_account_id: null,
        );

        // Rule: DEBIT Tax Payable to reduce it (if there is tax).
        if ($totalTax->isPositive()) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $taxAccountId,
                debit: $totalTax,
                credit: Money::of(0, $currencyCode),
                description: 'Tax Payable',
                partner_id: null,
                analytic_account_id: null,
            );
        }

        // Rule: CREDIT Accounts Receivable to reduce the customer's debt.
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $arAccountId,
            debit: Money::of(0, $currencyCode),
            credit: $totalAmount,
            description: 'Accounts Receivable',
            partner_id: $adjustment->original_invoice_id ? ($adjustment->originalInvoice?->customer_id) : ($adjustment->original_vendor_bill_id ? ($adjustment->originalVendorBill?->vendor_id) : null),
            analytic_account_id: null,
        );

        // 4. Prepare the data payload for the action.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $adjustment->company_id,
            currency_id: $adjustment->currency_id,
            journal_id: $salesJournalId,
            entry_date: $adjustment->posted_at ?? now()->toDateString(),
            reference: 'CN-'.$adjustment->reference_number,
            description: 'Credit Note '.$adjustment->reference_number,
            source_type: \Modules\Inventory\Models\AdjustmentDocument::class,
            source_id: $adjustment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs
        );

        // 5. Execute the action and return the result.
        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
