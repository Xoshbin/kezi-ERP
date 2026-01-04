<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Modules\Accounting\Contracts\AdjustmentJournalEntryCreatorContract;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Modules\Inventory\Models\AdjustmentDocument;
use RuntimeException;

class CreateJournalEntryForAdjustmentAction implements AdjustmentJournalEntryCreatorContract
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $adjustment->load('company.currency', 'currency', 'originalInvoice', 'originalVendorBill');

        // 2. Dispatch to the appropriate handler based on document type.
        return match ($adjustment->type) {
            AdjustmentDocumentType::CreditNote => $this->createCreditNoteJournalEntry($adjustment, $user),
            AdjustmentDocumentType::DebitNote => $this->createDebitNoteJournalEntry($adjustment, $user),
            AdjustmentDocumentType::Miscellaneous => $this->createMiscellaneousJournalEntry($adjustment, $user),
        };
    }

    /**
     * Create journal entry for a Credit Note (Sales Return).
     *
     * Journal Entry Pattern:
     *   DEBIT  Sales Discount/Contra-Revenue (reduces revenue)
     *   DEBIT  Tax Payable (if applicable, reduces tax liability)
     *   CREDIT Accounts Receivable (reduces customer debt)
     */
    private function createCreditNoteJournalEntry(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        $company = $adjustment->company;
        $currencyCode = $adjustment->currency->code;

        // Validate required accounts for Credit Notes
        $arAccountId = $company->default_accounts_receivable_id;
        $salesDiscountAccountId = $company->default_sales_discount_account_id;
        $taxAccountId = $company->default_tax_account_id;
        $salesJournalId = $company->default_sales_journal_id;

        if (! $arAccountId || ! $salesDiscountAccountId || ! $taxAccountId || ! $salesJournalId) {
            throw new RuntimeException('Default accounting accounts for Credit Notes are not configured for this company.');
        }

        $lineDTOs = [];
        $totalAmount = $adjustment->total_amount;
        $totalTax = $adjustment->total_tax;
        $subtotal = $totalAmount->minus($totalTax);

        // DEBIT Sales Discount/Contra-Revenue (reduces gross revenue)
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $salesDiscountAccountId,
            debit: $subtotal,
            credit: Money::of(0, $currencyCode),
            description: 'Sales Returns - Revenue Reduction',
            partner_id: $adjustment->originalInvoice?->customer_id,
            analytic_account_id: null,
        );

        // DEBIT Tax Payable (reduces tax liability, if applicable)
        if ($totalTax->isPositive()) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $taxAccountId,
                debit: $totalTax,
                credit: Money::of(0, $currencyCode),
                description: 'Tax Payable Reduction',
                partner_id: null,
                analytic_account_id: null,
            );
        }

        // CREDIT Accounts Receivable (reduces customer debt)
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $arAccountId,
            debit: Money::of(0, $currencyCode),
            credit: $totalAmount,
            description: 'Accounts Receivable Reduction',
            partner_id: $adjustment->originalInvoice?->customer_id,
            analytic_account_id: null,
        );

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $adjustment->company_id,
            currency_id: $adjustment->currency_id,
            journal_id: $salesJournalId,
            entry_date: $adjustment->posted_at ?? now()->toDateString(),
            reference: 'CN-'.$adjustment->reference_number,
            description: 'Credit Note '.$adjustment->reference_number,
            source_type: AdjustmentDocument::class,
            source_id: $adjustment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }

    /**
     * Create journal entry for a Debit Note (Purchase Return).
     *
     * Journal Entry Pattern:
     *   DEBIT  Accounts Payable (reduces debt to vendor)
     *   CREDIT Purchase Returns/Contra-Expense (reduces expense)
     *   CREDIT Tax Receivable (if applicable, reduces tax asset)
     */
    private function createDebitNoteJournalEntry(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        $company = $adjustment->company;
        $currencyCode = $adjustment->currency->code;

        // Validate required accounts for Debit Notes
        $apAccountId = $company->default_accounts_payable_id;
        $purchaseReturnsAccountId = $company->default_purchase_returns_account_id;
        $taxReceivableAccountId = $company->default_tax_receivable_id;
        $purchaseJournalId = $company->default_purchase_journal_id;

        if (! $apAccountId || ! $purchaseReturnsAccountId || ! $purchaseJournalId) {
            throw new RuntimeException('Default accounting accounts for Debit Notes are not configured for this company. Required: Accounts Payable, Purchase Returns, and Purchase Journal.');
        }

        $lineDTOs = [];
        $totalAmount = $adjustment->total_amount;
        $totalTax = $adjustment->total_tax;
        $subtotal = $totalAmount->minus($totalTax);

        // DEBIT Accounts Payable (reduces vendor debt)
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $apAccountId,
            debit: $totalAmount,
            credit: Money::of(0, $currencyCode),
            description: 'Accounts Payable Reduction',
            partner_id: $adjustment->originalVendorBill?->vendor_id,
            analytic_account_id: null,
        );

        // CREDIT Purchase Returns (contra-expense, reduces COGS/Expense)
        $lineDTOs[] = new CreateJournalEntryLineDTO(
            account_id: $purchaseReturnsAccountId,
            debit: Money::of(0, $currencyCode),
            credit: $subtotal,
            description: 'Purchase Returns - Expense Reduction',
            partner_id: $adjustment->originalVendorBill?->vendor_id,
            analytic_account_id: null,
        );

        // CREDIT Tax Receivable (reduces tax asset, if applicable)
        if ($totalTax->isPositive() && $taxReceivableAccountId) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $taxReceivableAccountId,
                debit: Money::of(0, $currencyCode),
                credit: $totalTax,
                description: 'Tax Receivable Reduction',
                partner_id: null,
                analytic_account_id: null,
            );
        }

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $adjustment->company_id,
            currency_id: $adjustment->currency_id,
            journal_id: $purchaseJournalId,
            entry_date: $adjustment->posted_at ?? now()->toDateString(),
            reference: 'DN-'.$adjustment->reference_number,
            description: 'Debit Note '.$adjustment->reference_number,
            source_type: AdjustmentDocument::class,
            source_id: $adjustment->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs
        );

        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }

    /**
     * Create journal entry for Miscellaneous Adjustments.
     *
     * For now, uses Credit Note logic as a fallback.
     * Can be extended for specific miscellaneous adjustment rules.
     */
    private function createMiscellaneousJournalEntry(AdjustmentDocument $adjustment, User $user): JournalEntry
    {
        // Fallback to Credit Note logic for miscellaneous adjustments
        return $this->createCreditNoteJournalEntry($adjustment, $user);
    }
}
