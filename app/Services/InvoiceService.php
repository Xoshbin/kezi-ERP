<?php

namespace App\Services;

use App\Events\InvoiceConfirmed;
use App\Exceptions\DeletionNotAllowedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;

class InvoiceService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    public function delete(Invoice $invoice): bool
    {
        // Guard Clause: Only allow deleting if the status is Invoice::TYPE_DRAFT.
        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            throw new DeletionNotAllowedException('Cannot delete a posted invoice.');
        }

        // If the guard passes, proceed with the deletion.
        return $invoice->delete();
    }

    public function confirm(Invoice $invoice, User $user): void
    {
        // Guard clause to prevent re-confirming.
        if ($invoice->status !== Invoice::TYPE_DRAFT) {
            // Or throw a custom exception
            return;
        }

        if ($invoice->company->isDateLocked($invoice->invoice_date)) {
            throw new \App\Exceptions\PeriodIsLockedException('The period for this invoice date is locked.');
        }

        DB::transaction(function () use ($invoice, $user) {
            $invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);
            $invoice->status = Invoice::TYPE_POSTED;
            $invoice->posted_at = now();

            $journalEntry = (new CreateJournalEntryForInvoiceAction())->execute($invoice, $user);
            $invoice->journal_entry_id = $journalEntry->id;

            $invoice->save();

            InvoiceConfirmed::dispatch($invoice);
        });
    }

    private function getNextInvoiceNumber(Company $company): string
    {
        // Simple (but not race-condition-proof) way to get the next number.
        $lastNumber = Invoice::where('company_id', $company->id)
            ->whereNotNull('invoice_number')
            ->count();

        // Format it nicely, e.g., INV-00001
        return 'INV-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    public function resetToDraft(Invoice $invoice, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('resetToDraft', $invoice);

        DB::transaction(function () use ($invoice, $user, $reason) {
            // THE FIX: Call delete() on the relationship builder `journalEntry()`
            // This issues a direct query and bypasses the model observer.
            $invoice->journalEntry()->delete();

            $newLog = [
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $reason,
            ];
            $logs = $invoice->reset_to_draft_log ?? [];
            array_unshift($logs, $newLog);

            $invoice->update([
                'status' => Invoice::TYPE_DRAFT,
                'journal_entry_id' => null,
                'posted_at' => null,
                'invoice_number' => null,
                'reset_to_draft_log' => $logs,
            ]);
        });
    }
}
