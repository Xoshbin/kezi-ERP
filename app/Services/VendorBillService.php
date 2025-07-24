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
    /**
     * Create a new draft vendor bill.
     */
    public function create(array $data): VendorBill
    {
        return DB::transaction(function () use ($data) {
            $vendorBill = new VendorBill();
            $vendorBill->fill(collect($data)->except('lines')->all());
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

        // Authorize the action using a Policy.
        Gate::forUser($user)->authorize('confirm', $vendorBill);

        DB::transaction(function () use ($vendorBill, $user) {
            // 1. Recalculate totals to ensure accuracy before posting.
            $this->recalculateBillTotals($vendorBill);

            // 2. Update status and posting date.
            $vendorBill->status = VendorBill::TYPE_POSTED;
            $vendorBill->posted_at = now();

            // 3. Create the accounting entry.
            $journalEntry = $this->createJournalEntryForBill($vendorBill, $user);
            $vendorBill->journal_entry_id = $journalEntry->id;

            $vendorBill->save();

            VendorBillConfirmed::dispatch($vendorBill);
        });
    }

    /**
     * Update a draft vendor bill.
     */
    public function update(VendorBill $vendorBill, array $data): VendorBill
    {
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot modify a non-draft vendor bill.');
        }

        DB::transaction(function () use ($vendorBill, $data) {
            if (isset($data['lines'])) {
                $vendorBill->lines()->delete();
                $vendorBill->lines()->createMany($data['lines']);
            }

            $this->recalculateBillTotals($vendorBill);

            $vendorBill->update(collect($data)->except('lines')->all());

            // We save again to persist the recalculated totals.
            $vendorBill->save();
        });

        return $vendorBill;
    }

    /**
     * Delete a draft vendor bill.
     */
    public function delete(VendorBill $vendorBill): bool
    {
        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new DeletionNotAllowedException('Cannot delete a posted vendor bill.');
        }
        return $vendorBill->delete();
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
     * Create the corresponding journal entry for a posted vendor bill.
     */
    private function createJournalEntryForBill(VendorBill $vendorBill, User $user): JournalEntry
    {
        // Get default accounts from your config.
        $apAccountId = config('accounting.defaults.accounts_payable_id');
        $taxAccountId = config('accounting.defaults.tax_receivable_id'); // Tax on purchases is an asset
        $purchaseJournalId = config('accounting.defaults.purchase_journal_id');

        $lines = [];

        // 1. The Credit Line (Total amount owed to the vendor)
        $lines[] = [
            'account_id' => $apAccountId,
            'credit' => $vendorBill->total_amount,
        ];

        // 2. The Debit Lines (Expense and Tax from each bill line)
        foreach ($vendorBill->lines as $billLine) {
            // Debit the expense account for the line's subtotal
            $lines[] = [
                'account_id' => $billLine->expense_account_id,
                'debit' => $billLine->subtotal,
                'description' => $billLine->description,
            ];

            // Debit the tax account if there is tax
            if ($billLine->total_line_tax > 0) {
                $lines[] = [
                    'account_id' => $taxAccountId,
                    'debit' => $billLine->total_line_tax,
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

        return (new JournalEntryService())->create($journalEntryData);
    }
}
