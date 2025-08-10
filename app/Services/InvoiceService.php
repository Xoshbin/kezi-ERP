<?php

namespace App\Services;

use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Enums\Sales\InvoiceStatus;
use Brick\Math\RoundingMode;
use App\Events\InvoiceConfirmed;
use Illuminate\Support\Facades\DB;
use App\Enums\Products\ProductType;
use Illuminate\Support\Facades\Gate;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockMoveStatus;
use Illuminate\Support\Facades\Validator;
use App\Models\AuditLog; // Add this import
use App\Services\Accounting\LockDateService;
use App\Services\Inventory\StockMoveService;
use App\Exceptions\UpdateNotAllowedException;
use Illuminate\Validation\ValidationException;
use App\Exceptions\DeletionNotAllowedException;
use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Actions\Accounting\CreateJournalEntryForInvoiceAction;

class InvoiceService
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected JournalEntryService $journalEntryService,
        protected StockMoveService $stockMoveService,
        protected CreateJournalEntryForInvoiceAction $createJournalEntryForInvoiceAction
    ) {
    }

    public function delete(Invoice $invoice): bool
    {
        // Guard Clause: Only allow deleting if the status is InvoiceStatus::Draft.
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new DeletionNotAllowedException('Cannot delete a posted invoice.');
        }

        // If the guard passes, proceed with the deletion.
        return $invoice->delete();
    }

    public function confirm(Invoice $invoice, User $user): void
    {
        // Guard clause to prevent re-confirming.
        if ($invoice->status !== InvoiceStatus::Draft) {
            // Or throw a custom exception
            return;
        }

        $this->lockDateService->enforce(\App\Models\Company::find($invoice->company_id), \Carbon\Carbon::parse($invoice->invoice_date));

        DB::transaction(function () use ($invoice, $user) {
            $invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);
            $invoice->status = InvoiceStatus::Posted;
            $invoice->posted_at = now();

            $journalEntry = $this->createJournalEntryForInvoiceAction->execute($invoice, $user);
            $invoice->journal_entry_id = $journalEntry->id;

            $invoice->save();

            // Create stock moves for storable products
            foreach ($invoice->invoiceLines as $line) {
                if ($line->product && $line->product->product_type === ProductType::Storable->value) {
                    (new CreateStockMoveAction($this->stockMoveService))->execute(new CreateStockMoveDTO(
                        company_id: $invoice->company_id,
                        product_id: $line->product_id,
                        quantity: $line->quantity,
                        from_location_id: $invoice->company->stock_location_id,
                        to_location_id: $invoice->partner->stock_location_id,
                        move_type: StockMoveType::OUTGOING,
                        status: StockMoveStatus::DONE,
                        move_date: $invoice->invoice_date,
                        reference: $invoice->invoice_number,
                        source_id: $invoice->id,
                        source_type: Invoice::class,
                        created_by_user_id: $user->id,
                    ));
                }
            }

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

    /**
     * Resets a posted invoice back to draft status with a detailed audit log.
     */
    public function resetToDraft(Invoice $invoice, User $user, string $reason): void
    {
        if ($invoice->status !== InvoiceStatus::Posted) {
            throw new \Exception('Only posted invoices can be reset to draft.');
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            $originalEntry = $invoice->journalEntry;
            if (!$originalEntry) {
                throw new \Exception('Cannot reset an invoice without a journal entry.');
            }

            // Step 1: Create a detailed audit log explaining the action.
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'reset_to_draft',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'description' => 'Invoice Reset to Draft: ' . $reason,
                'old_values' => ['status' => $invoice->status],
                'new_values' => ['status' => InvoiceStatus::Draft],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Use the service to create the reversing journal entry.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Reset to Draft of Invoice ' . $invoice->invoice_number . ': ' . $reason,
                $user
            );

            // Step 3: Store original values before clearing them
            $originalInvoiceNumber = $invoice->invoice_number;
            $originalPostedAt = $invoice->posted_at;

            // Step 4: Add to reset log for audit trail
            $resetLog = $invoice->reset_to_draft_log ?? [];
            $resetLog[] = [
                'reset_at' => now()->toISOString(),
                'reset_by' => $user->id,
                'reason' => $reason,
                'original_invoice_number' => $originalInvoiceNumber,
                'original_posted_at' => $originalPostedAt?->toISOString(),
            ];

            // Step 5: Update the invoice's status and clear posted fields.
            $invoice->status = InvoiceStatus::Draft;
            $invoice->posted_at = null;
            $invoice->journal_entry_id = null;
            $invoice->invoice_number = null;
            $invoice->reset_to_draft_log = $resetLog;

            $invoice->save();
        });
    }

    /**
     * Cancels a posted invoice by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(Invoice $invoice, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $invoice); // You may want a specific policy for this

        if ($invoice->status !== InvoiceStatus::Posted) {
            throw new \Exception('Only posted invoices can be cancelled.');
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            $originalEntry = $invoice->journalEntry;
            if (!$originalEntry) {
                throw new \Exception('Cannot cancel an invoice without a journal entry.');
            }

            // Step 1: Create a detailed audit log explaining the action.
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'cancellation',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'description' => 'Invoice Cancelled: ' . $reason,
                'old_values' => ['status' => $invoice->status],
                'new_values' => ['status' => InvoiceStatus::Cancelled],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Use the service to create the reversing journal entry.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Invoice ' . $invoice->invoice_number . ': ' . $reason,
                $user
            );

            // Step 3: Update the invoice's status to reflect the cancellation.
            $invoice->status = InvoiceStatus::Cancelled;
            $invoice->save();
        });
    }
}
