<?php

namespace App\Services;

use App\Events\VendorBillConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
class VendorBillService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected AccountingValidationService $accountingValidationService
    ) {
    }

    /**
     * Create a new draft vendor bill.
     */
    public function create(array $data): VendorBill
    {
        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($data['company_id'], $data['bill_date']);

        return DB::transaction(function () use ($data) {
            $vendorBill = new VendorBill();
            $vendorBillData = collect($data)->except('lines')->all();
            if (isset($vendorBillData['partner_id'])) {
                $vendorBillData['vendor_id'] = $vendorBillData['partner_id'];
                unset($vendorBillData['partner_id']);
            }
            $vendorBill->fill($vendorBillData);
            $vendorBill->status = VendorBill::TYPE_DRAFT;
            $vendorBill->total_amount = 0; // Initialize total_amount to 0
            $vendorBill->total_tax = 0; // Initialize total_tax to 0
            $vendorBill->save(); // Save the vendor bill first to get an ID

            if (isset($data['lines'])) {
                $vendorBill->lines()->createMany($data['lines']);
            }

            $this->recalculateBillTotals($vendorBill);
            $vendorBill->save();

            return $vendorBill;
        });
    }

    /**
     * Confirm a draft vendor bill, post it, and create the corresponding journal entry.
     */
    public function confirm(VendorBill $vendorBill, User $user): void
    {
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            return; // Or throw an exception
        }

        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        Gate::forUser($user)->authorize('confirm', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            $this->_confirmAndPostBill($vendorBill, $user);
        });
    }

    /**
     * Update a draft vendor bill. Can also handle posting in the same action.
     */
    public function update(VendorBill $vendorBill, array $data, User $user): VendorBill
    {
        // First, check if the entry's date is in a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $data['bill_date'] ?? $vendorBill->bill_date);

        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot update a posted vendor bill.');
        }

        $isPosting = isset($data['status']) && $data['status'] === VendorBill::TYPE_POSTED;

        DB::transaction(function () use ($vendorBill, $data, $user, $isPosting) {
            $updateData = collect($data)->except('status')->all();

            if (isset($updateData['partner_id'])) {
                $updateData['vendor_id'] = $updateData['partner_id'];
                unset($updateData['partner_id']);
            }

            if (isset($updateData['lines'])) {
                $vendorBill->lines()->delete();
                $vendorBill->lines()->createMany($updateData['lines']);
                unset($updateData['lines']);
            }

            $vendorBill->fill($updateData);

            if ($isPosting) {
                $this->_confirmAndPostBill($vendorBill, $user);
            } else {
                $this->recalculateBillTotals($vendorBill);
                $vendorBill->save();
            }
        });

        return $vendorBill;
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {

        // First, check if the entry's date is in a locked period.
        // This applies to ALL entries, whether draft or posted, if their date falls within a locked period.
        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $vendorBill->bill_date);

        // Block deletion if the entry has been posted.
        // Block deletion if the entry has been posted. This is the non-negotiable immutability rule.
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new DeletionNotAllowedException(
                'Cannot delete a posted vendor bill. Corrections must be made with a new reversal entry.'
            );
        }

        // Proceed with deletion for draft entries.
        // Using a transaction is good practice, though Eloquent's delete handles this well.
        return DB::transaction(function () use ($vendorBill) {
            // Deleting the JournalEntry will also delete its lines if foreign keys
            // are configured with `onDelete('cascade')`.
            return $vendorBill->delete();
        });
    }

    /**
     * Reset a posted vendor bill back to draft.
     */
    public function resetToDraft(VendorBill $vendorBill, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('resetToDraft', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user, $reason) {
            $vendorBill->journalEntry()->delete();

            $newLog = [
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $reason,
            ];
            $logs = $vendorBill->reset_to_draft_log ?: [];
            array_unshift($logs, $newLog);

            $vendorBill->status = VendorBill::TYPE_DRAFT;
            $vendorBill->journal_entry_id = null;
            $vendorBill->posted_at = null;
            $vendorBill->reset_to_draft_log = $logs;

            $vendorBill->save();
        });
    }

    /**
     * Recalculate the total_amount and total_tax from the bill's lines.
     */
    public function recalculateBillTotals(VendorBill $vendorBill): void
    {
        $vendorBill->load('lines');

        // Sums are performed on integer values due to the MoneyCast.
        $totalTax = $vendorBill->lines->sum('total_line_tax');
        $subtotal = $vendorBill->lines->sum('subtotal');

        $vendorBill->total_tax = $totalTax;
        $vendorBill->total_amount = $subtotal + $totalTax;
    }

    /**
     * Internal method to perform the actual posting logic. Assumes it's running inside a transaction.
     */
    private function _confirmAndPostBill(VendorBill $vendorBill, User $user): void
    {
        // 1. Recalculate totals to ensure accuracy before posting.
        $this->recalculateBillTotals($vendorBill);

        // 2. Update status and posting date.
        $vendorBill->status = VendorBill::TYPE_POSTED;
        $vendorBill->posted_at = now();

        // 3. Create the accounting entry.
        $journalEntry = $this->createJournalEntryForBill($vendorBill, $user);
        $vendorBill->journal_entry_id = $journalEntry->id;

        // 4. Save all changes to the bill.
        $vendorBill->save();

        // 5. Dispatch event.
        VendorBillConfirmed::dispatch($vendorBill);
    }

    /**
     * Create the corresponding journal entry for a posted vendor bill.
     */
    private function createJournalEntryForBill(VendorBill $vendorBill, User $user): JournalEntry
    {
        // Get default accounts from the bill's company.
        $company = $vendorBill->company;
        $apAccountId = $company->default_accounts_payable_id;
        $taxAccountId = $company->default_tax_receivable_id;
        $purchaseJournalId = $company->default_purchase_journal_id;

        // Explicitly fail if configuration is missing for the company.
        if (!$apAccountId || !$taxAccountId || !$purchaseJournalId) {
            throw new \RuntimeException('Default accounting accounts are not configured for this company. Please set them in the company settings.');
        }

        // A credit note should reverse the debit/credit entries.
        $isCreditNote = $vendorBill->type === 'credit_note';

        $lines = [];

        // 1. The Accounts Payable Line: Credit for bills, Debit for credit notes.
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => $isCreditNote ? $vendorBill->total_amount : 0,
            'credit' => !$isCreditNote ? $vendorBill->total_amount : 0,
            'description' => 'Accounts Payable',
        ];

        // 2. The Debit/Credit lines for expenses and taxes.
        foreach ($vendorBill->lines as $billLine) {
            if (empty($billLine->expense_account_id)) {
                throw new \RuntimeException("Expense account is not set for vendor bill line #{$billLine->id}.");
            }
            // Expense line
            $lines[] = [
                'account_id' => $billLine->expense_account_id,
                'debit' => !$isCreditNote ? $billLine->subtotal : 0,
                'credit' => $isCreditNote ? $billLine->subtotal : 0,
                'description' => $billLine->description,
            ];

            // Tax line
            if ($billLine->total_line_tax > 0) {
                $lines[] = [
                    'account_id' => $taxAccountId,
                    'debit' => !$isCreditNote ? $billLine->total_line_tax : 0,
                    'credit' => $isCreditNote ? $billLine->total_line_tax : 0,
                    'description' => 'Tax for bill ' . $vendorBill->bill_reference,
                ];
            }
        }

        $journalEntryData = [
            'company_id' => $vendorBill->company_id,
            'journal_id' => $purchaseJournalId,
            'entry_date' => $vendorBill->posted_at,
            'reference' => $vendorBill->bill_reference,
            'description' => 'Vendor Bill ' . $vendorBill->bill_reference,
            'source_type' => VendorBill::class,
            'source_id' => $vendorBill->id,
            'lines' => $lines,
            'created_by_user_id' => $user->id,
        ];

        return $this->journalEntryService->create($journalEntryData);
    }
}
