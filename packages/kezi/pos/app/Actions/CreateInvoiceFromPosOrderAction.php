<?php

namespace Kezi\Pos\Actions;

use Carbon\Carbon;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\Pos\Models\PosOrder;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;

class CreateInvoiceFromPosOrderAction
{
    public function __construct(
        protected InvoiceService $invoiceService,
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

        // 2. Determine Journal
        $journalId = $profile->default_payment_journal_id;
        if (! $journalId) {
            // Fallback to company default sales journal
            $journal = Journal::where('company_id', $companyId)
                ->where('type', \Kezi\Accounting\Enums\Accounting\JournalType::Sale)
                ->first();
            $journalId = $journal->id ?? null;
        }

        if (! $journalId) {
            throw new \Exception("No payment journal found for POS Order {$order->order_number}. Configure POS Profile or Company Sales Journal.");
        }

        // 3. Create Invoice
        $invoiceData = [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'journal_id' => $journalId,
            'currency_id' => $order->currency_id,
            'invoice_number' => 'POS-'.$order->order_number, // Temporary, confirms service might overwrite or sequence service used during posting
            // 'journal_entry_id' => will be set by posting
            // 'fiscal_position_id' => null, // or from customer
            'invoice_date' => $order->ordered_at ? Carbon::parse($order->ordered_at) : now(),
            'due_date' => $order->ordered_at ? Carbon::parse($order->ordered_at) : now(), // POS is immediate payment usually
            'status' => InvoiceStatus::Draft, // Start as draft, then post
            'total_amount' => $order->total_amount,
            'total_tax' => $order->total_tax,
            'exchange_rate_at_creation' => 1.0, // Assuming same currency for now or handled by service
        ];

        // Create the invoice
        $invoice = Invoice::create($invoiceData);

        // 4. Create Invoice Lines
        foreach ($order->lines as $line) {
            $incomeAccountId = $profile->default_income_account_id;
            if (! $incomeAccountId && $line->product) {
                $incomeAccountId = $line->product->income_account_id ?? $line->product->category?->income_account_id;
            }

            if (! $incomeAccountId) {
                // Fallback to a default company account if needed, or null and let validation fail if strict
                // For now, we try our best.
            }

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'company_id' => $companyId,
                'product_id' => $line->product_id,
                'description' => $line->product?->name ?? 'POS Item',
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

        // 6. Link to POS Order
        $order->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }
}
