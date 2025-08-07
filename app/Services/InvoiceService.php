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
use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Models\AuditLog; // Add this import
use App\Services\Inventory\StockMoveService;

class InvoiceService
{
    public function __construct(
        protected JournalEntryService $journalEntryService,
        protected StockMoveService $stockMoveService,
        protected CreateJournalEntryForInvoiceAction $createJournalEntryForInvoiceAction
    ) {
    }

    public function delete(Invoice $invoice): bool
    {
        // Guard Clause: Only allow deleting if the status is Invoice::STATUS_DRAFT.
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw new DeletionNotAllowedException('Cannot delete a posted invoice.');
        }

        // If the guard passes, proceed with the deletion.
        return $invoice->delete();
    }

    public function confirm(Invoice $invoice, User $user): void
    {
        // Guard clause to prevent re-confirming.
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            // Or throw a custom exception
            return;
        }

        if ($invoice->company->isDateLocked($invoice->invoice_date)) {
            throw new \App\Exceptions\PeriodIsLockedException('The period for this invoice date is locked.');
        }

        DB::transaction(function () use ($invoice, $user) {
            $invoice->invoice_number = $this->getNextInvoiceNumber($invoice->company);
            $invoice->status = Invoice::STATUS_POSTED;
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
     * Cancels a posted invoice by creating a reversing journal entry and a detailed audit log.
     */
    public function cancel(Invoice $invoice, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $invoice); // You may want a specific policy for this

        if ($invoice->status !== Invoice::STATUS_POSTED) {
            throw new \Exception('Only posted invoices can be cancelled.');
        }

        DB::transaction(function () use ($invoice, $user, $reason) {
            $originalEntry = $invoice->journalEntry;
            if (!$originalEntry) {
                throw new \Exception('Cannot cancel an invoice without a journal entry.');
            }

            // Step 1: Create a detailed audit log explaining the action.
            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'cancellation',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'description' => 'Invoice Cancelled: ' . $reason,
                'old_values' => ['status' => $invoice->status],
                'new_values' => ['status' => Invoice::STATUS_CANCELLED],
                'ip_address' => request()->ip(),
            ]);

            // Step 2: Use the service to create the reversing journal entry.
            $this->journalEntryService->createReversal(
                $originalEntry,
                'Cancellation of Invoice ' . $invoice->invoice_number . ': ' . $reason,
                $user
            );

            // Step 3: Update the invoice's status to reflect the cancellation.
            $invoice->status = Invoice::STATUS_CANCELLED;
            $invoice->save();
        });
    }
}
