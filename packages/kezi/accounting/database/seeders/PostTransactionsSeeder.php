<?php

namespace Kezi\Accounting\Database\Seeders;

use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Kezi\Payment\Models\Payment;
use Kezi\Payment\Services\PaymentService;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Services\VendorBillService;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Services\InvoiceService;

class PostTransactionsSeeder extends Seeder
{
    /**
     * Post some transactions to create realistic financial data for testing.
     * This follows proper business logic by using the services to post transactions.
     *
     * This seeder is NOT included in the main DatabaseSeeder to avoid interfering
     * with tests that expect draft transactions. Run manually when needed:
     * php artisan db:seed --class=PostTransactionsSeeder
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Skipping transaction posting.');

            return;
        }

        $invoiceService = app(InvoiceService::class);
        $vendorBillService = app(VendorBillService::class);
        $paymentService = app(PaymentService::class);

        // Post some invoices to create customer receivables
        // Only post 1 invoice to leave enough draft invoices for tests
        $invoicesToPost = Invoice::where('status', 'draft')
            ->whereIn('invoice_number', ['INV-001'])
            ->get();

        foreach ($invoicesToPost as $invoice) {
            try {
                $invoiceService->confirm($invoice, $user);
                $this->command->info("Posted invoice: {$invoice->invoice_number}");
            } catch (Exception $e) {
                $this->command->error("Failed to post invoice {$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        // Post some vendor bills to create vendor payables
        $vendorBillsToPost = VendorBill::where('status', 'draft')
            ->limit(2)
            ->get();

        foreach ($vendorBillsToPost as $vendorBill) {
            try {
                $vendorBillService->post($vendorBill, $user);
                $this->command->info("Posted vendor bill: {$vendorBill->bill_reference}");
            } catch (Exception $e) {
                $this->command->error("Failed to post vendor bill {$vendorBill->bill_reference}: {$e->getMessage()}");
            }
        }

        // Confirm some payments to create payment transactions
        // Reduce to 2 payments to keep the seeder minimal but still demonstrate functionality
        $paymentsToConfirm = Payment::whereNull('status')
            ->orWhere('status', 'draft')
            ->limit(2)
            ->get();

        foreach ($paymentsToConfirm as $payment) {
            try {
                $paymentService->confirm($payment, $user);
                $this->command->info("Confirmed payment: {$payment->reference}");
            } catch (Exception $e) {
                $this->command->error("Failed to confirm payment {$payment->reference}: {$e->getMessage()}");
            }
        }

        $this->command->info('Transaction posting completed!');
    }
}
