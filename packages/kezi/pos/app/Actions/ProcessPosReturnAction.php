<?php

namespace Kezi\Pos\Actions;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Services\CurrencyConverterService;
use Kezi\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;

class ProcessPosReturnAction
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected CreatePaymentAction $createPaymentAction,
        protected StockMoveService $stockMoveService,
        protected \Kezi\Payment\Services\PaymentService $paymentService,
        protected CurrencyConverterService $currencyConverter,
    ) {}

    /**
     * Process a complete POS return
     * This includes:
     * 1. Create credit note (reverse invoice)
     * 2. Create refund payment
     * 3. Create stock move for returned items (if restocking)
     */
    public function execute(PosReturn $return, User $user): PosReturn
    {
        return DB::transaction(function () use ($return, $user) {
            if ($return->status !== PosReturnStatus::Approved) {
                throw new \InvalidArgumentException('Only approved returns can be processed');
            }

            $return->update(['status' => PosReturnStatus::Processing]);

            // Step 1: Create Credit Note
            $creditNote = $this->createCreditNote($return, $user);
            $return->update(['credit_note_id' => $creditNote->id]);

            // Step 2: Create Refund Payment
            $payment = $this->createRefundPayment($return, $creditNote, $user);
            $return->update(['payment_reversal_id' => $payment->id]);

            // Step 3: Create Stock Move for returned items (if applicable)
            if ($this->shouldRestockItems($return)) {
                $stockMove = $this->createStockMove($return, $user);
                $return->update(['stock_move_id' => $stockMove->id]);
            }

            // Mark as completed
            $return->update(['status' => PosReturnStatus::Completed]);

            $freshReturn = $return->fresh(['creditNote', 'paymentReversal', 'stockMove']);

            \Kezi\Pos\Events\PosReturnProcessed::dispatch($freshReturn);

            return $freshReturn;
        });
    }

    protected function createCreditNote(PosReturn $return, User $user): Invoice
    {
        $originalOrder = $return->originalOrder;
        $profile = $return->session->profile;

        // Find sales journal
        $journal = \Kezi\Accounting\Models\Journal::where('company_id', $return->company_id)
            ->where('type', \Kezi\Accounting\Enums\Accounting\JournalType::Sale)
            ->first();

        if (! $journal) {
            throw new \Exception("No Sales Journal found for company {$return->company_id}");
        }

        $refundAmount = $return->refund_amount->multipliedBy(-1);
        $currency = $return->currency;

        // Create credit note (negative invoice)
        $creditNote = Invoice::create([
            'company_id' => $return->company_id,
            'customer_id' => $originalOrder->customer_id,
            'journal_id' => $journal->id,
            'currency_id' => $return->currency_id,
            'invoice_number' => 'CN-'.$return->return_number,
            'invoice_date' => $return->return_date,
            'due_date' => $return->return_date,
            'status' => InvoiceStatus::Draft,
            'total_amount' => $refundAmount,
            'total_tax' => Money::of(0, $currency->code),
            'exchange_rate_at_creation' => $this->getExchangeRate($return),
            'notes' => "Credit Note for Return {$return->return_number}",
        ]);

        // Create invoice lines from return lines
        foreach ($return->lines as $returnLine) {
            $incomeAccountId = $profile->default_income_account_id;
            if (! $incomeAccountId && $returnLine->product) {
                $incomeAccountId = $returnLine->product->income_account_id;
            }

            InvoiceLine::create([
                'invoice_id' => $creditNote->id,
                'company_id' => $return->company_id,
                'product_id' => $returnLine->product_id,
                'description' => 'Return: '.$returnLine->product->name,
                'quantity' => -$returnLine->quantity_returned,
                'unit_price' => $returnLine->unit_price,
                'subtotal' => $returnLine->refund_amount->multipliedBy(-1),
                'total_line_tax' => Money::of(0, $currency->code),
                'income_account_id' => $incomeAccountId,
            ]);
        }

        // Recalculate totals and post
        $creditNote->calculateTotalsFromLines();
        $creditNote->save();

        $this->invoiceService->confirm($creditNote, $user);

        return $creditNote;
    }

    protected function createRefundPayment(PosReturn $return, Invoice $creditNote, User $user): \Kezi\Payment\Models\Payment
    {
        $profile = $return->session->profile;

        $paymentJournalId = $profile->default_payment_journal_id;
        if (! $paymentJournalId) {
            throw new \Exception('No Payment Journal configured for POS Profile');
        }

        // Create outbound payment (refund)
        $paymentDto = new \Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO(
            company_id: $return->company_id,
            journal_id: $paymentJournalId,
            currency_id: $return->currency_id,
            payment_date: $return->return_date->toDateString(),
            payment_type: PaymentType::Outbound,
            payment_method: $this->mapRefundMethod($return->refund_method),
            paid_to_from_partner_id: $return->originalOrder->customer_id,
            amount: $creditNote->total_amount->abs(),
            document_links: [
                new \Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO(
                    document_type: 'invoice',
                    document_id: $creditNote->id,
                    amount_applied: $creditNote->total_amount->abs(),
                ),
            ],
            reference: 'Refund for Return '.$return->return_number,
        );

        $payment = $this->createPaymentAction->execute($paymentDto, $user);
        $this->paymentService->confirm($payment, $user);

        return $payment;
    }

    protected function createStockMove(PosReturn $return, User $user): \Kezi\Inventory\Models\StockMove
    {
        $profile = $return->session->profile;
        $returnPolicy = $profile->return_policy ?? [];

        // Get stock location from return policy or profile default
        $locationId = (int) ($returnPolicy['default_restock_location_id'] ?? $profile->stock_location_id);

        if (! $locationId) {
            throw new \Exception('No stock location configured for restocking');
        }

        $dto = new CreateStockMoveDTO(
            company_id: $return->company_id,
            move_type: StockMoveType::Incoming,
            status: StockMoveStatus::Draft,
            move_date: \Carbon\Carbon::parse($return->return_date),
            created_by_user_id: $user->id,
            product_lines: $this->buildProductLines($return, $locationId),
            reference: 'Return '.$return->return_number,
            description: 'Stock return from POS Return '.$return->return_number,
            source_type: PosReturn::class,
            source_id: $return->id,
        );

        $stockMove = $this->stockMoveService->createMove($dto);

        // Confirm the stock move immediately
        $confirmDto = new ConfirmStockMoveDTO(
            stock_move_id: $stockMove->id,
        );

        return $this->stockMoveService->confirmMove($confirmDto);
    }

    protected function buildProductLines(PosReturn $return, int $locationId): array
    {
        $lines = [];

        foreach ($return->lines as $returnLine) {
            if ($returnLine->restock) {
                // Determine destination based on item condition
                $destLocationId = $this->getDestinationLocation($returnLine, $locationId);

                $lines[] = new CreateStockMoveProductLineDTO(
                    product_id: $returnLine->product_id,
                    quantity: (float) $returnLine->quantity_returned,
                    from_location_id: 0, // Placeholder for External/Customer if null not allowed, but usually from_location can be null for receipts
                    to_location_id: $destLocationId,
                    description: 'Return line for '.$returnLine->product->name,
                );
            }
        }

        return $lines;
    }

    protected function getDestinationLocation(\Kezi\Pos\Models\PosReturnLine $line, int $defaultLocationId): int
    {
        $returnPolicy = $line->posReturn->session->profile->return_policy ?? [];

        // Route damaged/defective items to special location
        if (in_array($line->item_condition, ['damaged', 'defective'])) {
            return (int) ($returnPolicy['damaged_items_location_id']
                ?? $returnPolicy['default_restock_location_id']
                ?? $defaultLocationId);
        }

        return $defaultLocationId;
    }

    protected function shouldRestockItems(PosReturn $return): bool
    {
        return $return->lines->contains('restock', true);
    }

    protected function mapRefundMethod(?string $refundMethod): PaymentMethod
    {
        return match ($refundMethod) {
            'cash' => PaymentMethod::Cash,
            'bank_transfer' => PaymentMethod::BankTransfer,
            'store_credit' => PaymentMethod::Manual,
            default => PaymentMethod::Cash,
        };
    }

    protected function getExchangeRate(PosReturn $return): float
    {
        if ($return->currency_id === $return->company->currency_id) {
            return 1.0;
        }

        return $this->currencyConverter->getExchangeRate($return->currency, $return->return_date, $return->company)
            ?? $this->currencyConverter->getLatestExchangeRate($return->currency, $return->company)
            ?? 1.0;
    }
}
