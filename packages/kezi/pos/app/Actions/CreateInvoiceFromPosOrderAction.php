<?php

namespace Kezi\Pos\Actions;

use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Services\CurrencyConverterService;
use Kezi\Pos\Models\PosOrder;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;

class CreateInvoiceFromPosOrderAction
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected \Kezi\Payment\Services\PaymentService $paymentService,
        protected \Kezi\Payment\Actions\Payments\CreatePaymentAction $createPaymentAction,
        protected CurrencyConverterService $currencyConverter,
    ) {}

    public function execute(PosOrder $order): Invoice
    {
        $order->load(['lines.product', 'session.profile']);

        $profile = $order->session->profile;
        $companyId = $order->company_id;

        // 1. Determine Customer
        $customerId = $order->customer_id;
        if (! $customerId) {
            // Find or create a default "Walk-in Customer"
            // For now, let's try to find a partner named "Walk-in Customer" for the company
            // Or assume the PosProfile should have one (but we didn't add it in the migration yet as per request Step 6, just discussed it)
            // Let's search for "Walk-in Customer" or fallback to first customer or handle null if Invoice allows (Invoice usually requires customer)

            $walkIn = Partner::where('company_id', $companyId)
                ->where('name', 'Walk-in Customer')
                ->first();

            if (! $walkIn) {
                // If not found, use a generic one or create it?
                // Creating might be risky inside a sync if it fails validation.
                // Let's search for any partner if walk-in not found, or use a config.
                // For this iteration, let's try to get a partner with specific logic or skip if not found (which will fail invoice creation likely)
                // Let's create one if not exists to be safe and ensure flow works.
                $walkIn = Partner::firstOrCreate(
                    ['company_id' => $companyId, 'name' => 'Walk-in Customer'],
                    [
                        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
                        'is_active' => true,
                    ]
                );
            }
            $customerId = $walkIn->id;
        }

        // 2. Determine Journal for Invoice (Must be a Sales Journal)
        // Invoices are Sales documents, so they need a Sales Journal (Dr AR / Cr Income).
        // The POS Profile's default_payment_journal_id is for PAYMENTS (Dr Cash / Cr AR), not Invoices.

        $journal = Journal::where('company_id', $companyId)
            ->where('type', \Kezi\Accounting\Enums\Accounting\JournalType::Sale)
            ->first();

        if (! $journal) {
            throw new \Exception("No Sales Journal found for Company {$companyId}. Please configure a Sales Journal.");
        }
        $journalId = $journal->id;

        // 3. Create Invoice
        $invoiceData = [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'journal_id' => $journalId,
            'currency_id' => $order->currency_id,
            'invoice_number' => 'POS-'.$order->order_number, // Temporary, confirms service might overwrite or sequence service used during posting
            // 'journal_entry_id' => will be set by posting
            // 'fiscal_position_id' => null, // or from customer
            'invoice_date' => $order->ordered_at,
            'due_date' => $order->ordered_at, // POS is immediate payment usually
            'status' => InvoiceStatus::Draft, // Start as draft, then post
            'total_amount' => $order->total_amount,
            'total_tax' => $order->total_tax,
            'exchange_rate_at_creation' => $this->getExchangeRate($order),
        ];

        // Create the invoice
        $invoice = Invoice::create($invoiceData);

        // 4. Create Invoice Lines
        foreach ($order->lines as $line) {
            $incomeAccountId = $profile->default_income_account_id;
            if (! $incomeAccountId && $line->product) {
                $incomeAccountId = $line->product->income_account_id;
            }

            if (! $incomeAccountId) {
                // Fallback to a default company account if needed, or null and let validation fail if strict
                // For now, we try our best.
            }

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'company_id' => $companyId,
                'product_id' => $line->product_id,
                'description' => $line->product->name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->total_amount->minus($line->tax_amount), // Approximate subtotal if not stored directly
                'total_line_tax' => $line->tax_amount,
                'income_account_id' => $incomeAccountId,
                'tax_id' => null, // We might need to map tax from POS data if available, or rely on product defaults. POS sends tax_amount.
            ]);
        }

        // Recalculate totals to be sure
        $invoice->calculateTotalsFromLines();
        $invoice->save();

        // 5. Post the Invoice
        // This triggers journal entries and stock moves (via listeners)
        // We act as the user who opened the session
        if (! $order->session) {
            throw new \Exception("POS Order {$order->uuid} has no associated session.");
        }
        $user = $order->session->user;
        $this->invoiceService->confirm($invoice, $user);

        // 6. Register Payments
        // POS orders are paid immediately. We create one Payment per split-payment line.
        // Each payment clears part of the Receivable (AR) and debits the Cash/Bank account.

        $paymentJournalId = $profile->default_payment_journal_id;
        if (! $paymentJournalId) {
            throw new \Exception("No Payment Journal configured for POS Profile. Cannot register payment for Order {$order->order_number}.");
        }

        // Load the split payment rows (always present — sync action ensures at least one row)
        $order->load('payments');
        $orderPayments = $order->payments;

        if ($orderPayments->isEmpty()) {
            // Ultimate fallback: single payment from legacy field (should never happen post-migration)
            $orderPayments = collect([
                new \Kezi\Pos\Models\PosOrderPayment([
                    'payment_method' => $order->payment_method ?? \Kezi\Payment\Enums\Payments\PaymentMethod::Cash,
                    'amount' => $order->total_amount->getMinorAmount()->toInt(),
                    'amount_tendered' => $order->total_amount->getMinorAmount()->toInt(),
                    'change_given' => 0,
                ]),
            ]);
        }

        // Distribute invoice total proportionally across split payments.
        // For a single payment this is always the full invoice total.
        $invoiceTotal = $invoice->total_amount;
        $currencyCode = $order->currency->code;
        $totalPaidMinor = $orderPayments->sum('amount');

        foreach ($orderPayments as $index => $splitPayment) {
            // For the last payment take the remainder to avoid rounding drift
            if ($index === $orderPayments->count() - 1) {
                // Sum of all previously applied amounts
                $alreadyApplied = $orderPayments->take($index)
                    ->sum(fn ($p) => (int) round($invoiceTotal->getMinorAmount()->toInt() * ($p->amount / $totalPaidMinor)));
                $appliedMinor = $invoiceTotal->getMinorAmount()->toInt() - $alreadyApplied;
            } else {
                $appliedMinor = (int) round($invoiceTotal->getMinorAmount()->toInt() * ($splitPayment->amount / $totalPaidMinor));
            }

            $appliedAmount = \Brick\Money\Money::ofMinor($appliedMinor, $currencyCode);

            $paymentDto = new \Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO(
                company_id: $companyId,
                journal_id: $paymentJournalId,
                currency_id: $order->currency_id,
                payment_date: $order->ordered_at->toDateString(),
                payment_type: \Kezi\Payment\Enums\Payments\PaymentType::Inbound,
                payment_method: $splitPayment->payment_method,
                paid_to_from_partner_id: $customerId,
                amount: null, // Calculated from links
                document_links: [
                    new \Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO(
                        document_type: 'invoice',
                        document_id: $invoice->id,
                        amount_applied: $appliedAmount,
                    ),
                ],
                reference: 'POS Payment '.$order->order_number.($orderPayments->count() > 1 ? ' ('.($index + 1).'/'.$orderPayments->count().')' : ''),
            );

            $payment = $this->createPaymentAction->execute($paymentDto, $user);
            $this->paymentService->confirm($payment, $user);
        }

        // 6. Link to POS Order
        $order->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    protected function getExchangeRate(PosOrder $order): float
    {
        if ($order->currency_id === $order->company->currency_id) {
            return 1.0;
        }

        return $this->currencyConverter->getExchangeRate($order->currency, $order->ordered_at, $order->company)
            ?? $this->currencyConverter->getLatestExchangeRate($order->currency, $order->company)
            ?? 1.0;
    }
}
