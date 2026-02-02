<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Services\PaymentService;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreatePurchaseOrderAction;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Kezi\Purchase\Services\PurchaseOrderService;
use Kezi\Purchase\Services\VendorBillService;
use Kezi\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Kezi\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use Kezi\Sales\Actions\Sales\CreateSalesOrderAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Kezi\Sales\Services\InvoiceService;

/**
 * Scenario Three Seeder - Complete Business Workflow with Manual Inventory Recording
 *
 * This seeder demonstrates a complete accounting workflow with MANUAL inventory recording mode:
 * - Unlike ScenarioTwoSeeder (auto mode), stock pickings remain in draft state
 * - Warehouse staff must explicitly confirm pickings to trigger inventory journal entries
 *
 * Workflow Steps:
 * 1. Opening balance journal entry (loan to cash)
 * 2. Purchase order creation and confirmation
 * 3. Vendor bill creation and posting (stock picking remains in draft)
 * 3.5. Manual Warehouse Receipt - confirm incoming stock picking (triggers inventory entry)
 * 4. Vendor payment
 * 5. Sales order creation and confirmation (delivery picking remains in draft)
 * 5.5. Manual Delivery Confirmation - confirm delivery picking (triggers COGS entry)
 * 6. Customer invoice creation and posting
 * 7. Customer payment receipt
 *
 * This demonstrates the separation between purchasing/sales operations and physical warehouse operations.
 */
class ScenarioThreeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Get foundational data - requires main DatabaseSeeder to have been run first
            $company = Company::where('name', 'Kezi Solutions')->first();

            if (! $company) {
                $this->command->error('');
                $this->command->error('  ✗ ERROR: Foundational data not found!');
                $this->command->error('');
                $this->command->error('  This seeder requires the main DatabaseSeeder to be run first.');
                $this->command->error('  Please run: php artisan db:seed');
                $this->command->error('  Then run: php artisan db:seed --class=ScenarioThreeSeeder');
                $this->command->error('');

                throw new \RuntimeException('Foundational data not found. Run DatabaseSeeder first.');
            }

            // ============================================================
            // SETUP: Set company to MANUAL inventory recording mode
            // ============================================================
            $this->command->info('=== Starting Scenario Three: Manual Inventory Recording Workflow ===');
            $this->command->info('');
            $this->command->info('Setting company to MANUAL inventory recording mode...');

            $company->update([
                'inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING,
            ]);

            $this->command->info('   ✓ Company inventory mode: MANUAL_INVENTORY_RECORDING');
            $this->command->info('   → Stock pickings will require manual confirmation by warehouse staff');
            $this->command->info('');

            // Get user associated with the company (through pivot table)
            $user = User::where('email', 'admin@kezi.com')->firstOrFail();
            $iqdCurrency = Currency::where('code', 'IQD')->firstOrFail();

            // Get accounts
            $cashAccountIqd = Account::where('company_id', $company->id)->where('code', '110202')->firstOrFail();
            $loansPayable = Account::where('company_id', $company->id)->where('code', '250201')->firstOrFail();
            $incomeAccount = Account::where('company_id', $company->id)->where('code', '410102')->firstOrFail();

            // Get journals
            $openingBalanceJournal = Journal::where('company_id', $company->id)->where('short_code', 'OPEN')->firstOrFail();
            $cashJournalIqd = Journal::where('company_id', $company->id)->where('short_code', 'CSH-IQD')->firstOrFail();

            // Get partners
            $vendor = Partner::where('company_id', $company->id)->where('name', 'Paykar Tech Supplies')->firstOrFail();
            $customer = Partner::where('company_id', $company->id)->where('name', 'Hawre Trading Group')->firstOrFail();

            // Get product
            $product = Product::where('company_id', $company->id)->where('sku', 'GPU-RTX4090')->firstOrFail();

            // ============================================================
            // STEP 1: Opening Balance Journal Entry
            // Debit: Cash (IQD 2,500,000)
            // Credit: Loans Payable (IQD 2,500,000)
            // ============================================================
            $this->command->info('Step 1: Creating opening balance journal entry...');

            $openingAmount = Money::of(2500000, 'IQD');

            $journalEntryDto = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $openingBalanceJournal->id,
                currency_id: $iqdCurrency->id,
                entry_date: now()->toDateString(),
                reference: 'SC3-OPEN-001',
                description: 'Opening balance - Loan to cash for company startup (Scenario 3)',
                created_by_user_id: $user->id,
                is_posted: true,
                lines: [
                    new CreateJournalEntryLineDTO(
                        account_id: $cashAccountIqd->id,
                        debit: $openingAmount,
                        credit: Money::zero('IQD'),
                        description: 'Cash received from loan',
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                    new CreateJournalEntryLineDTO(
                        account_id: $loansPayable->id,
                        debit: Money::zero('IQD'),
                        credit: $openingAmount,
                        description: 'Loan payable for startup capital',
                        partner_id: null,
                        analytic_account_id: null,
                    ),
                ],
            );

            $openingJournalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDto);
            $this->command->info("   ✓ Opening balance journal entry created (ID: {$openingJournalEntry->id})");

            // ============================================================
            // STEP 2: Create Purchase Order for 10 items
            // ============================================================
            $this->command->info('Step 2: Creating purchase order for 10 GPU-RTX4090...');

            $purchaseUnitPrice = Money::of(2500000, 'IQD'); // 2,500,000 IQD per unit

            $purchaseOrderDto = new CreatePurchaseOrderDTO(
                company_id: $company->id,
                vendor_id: $vendor->id,
                currency_id: $iqdCurrency->id,
                created_by_user_id: $user->id,
                reference: 'SC3-PO-001',
                po_date: Carbon::now(),
                expected_delivery_date: Carbon::now()->addDays(7),
                exchange_rate_at_creation: 1.0,
                notes: 'Purchase for Scenario Three - Manual Inventory Workflow',
                lines: [
                    new CreatePurchaseOrderLineDTO(
                        product_id: $product->id,
                        description: $product->name,
                        quantity: 10,
                        unit_price: $purchaseUnitPrice,
                        tax_id: null,
                    ),
                ],
            );

            $purchaseOrder = app(CreatePurchaseOrderAction::class)->execute($purchaseOrderDto);
            $this->command->info("   ✓ Purchase order created (ID: {$purchaseOrder->id}, Total: {$purchaseOrder->total_amount})");

            // Confirm the purchase order
            app(PurchaseOrderService::class)->confirm($purchaseOrder, $user);
            $purchaseOrder->refresh();
            $this->command->info("   ✓ Purchase order confirmed (PO#: {$purchaseOrder->po_number})");

            // ============================================================
            // STEP 3: Create Vendor Bill from Purchase Order and Post
            // Note: In MANUAL mode, posting the bill does NOT auto-create stock moves
            // ============================================================
            $this->command->info('Step 3: Creating vendor bill from purchase order...');

            $vendorBillDto = new CreateVendorBillFromPurchaseOrderDTO(
                purchase_order_id: $purchaseOrder->id,
                bill_reference: 'SC3-VENDOR-INV-001',
                bill_date: now()->toDateString(),
                accounting_date: now()->toDateString(),
                due_date: now()->addDays(30)->toDateString(),
                created_by_user_id: $user->id,
            );

            $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($vendorBillDto);
            $this->command->info("   ✓ Vendor bill created (ID: {$vendorBill->id}, Total: {$vendorBill->total_amount})");

            // Post the vendor bill
            app(VendorBillService::class)->post($vendorBill, $user);
            $vendorBill->refresh();
            $this->command->info("   ✓ Vendor bill posted (Ref: {$vendorBill->bill_reference})");
            $this->command->info('   → In MANUAL mode, no stock moves are auto-created on bill posting');

            // ============================================================
            // STEP 3.5: Manual Warehouse Receipt
            // Warehouse staff physically receives the goods and confirms the picking
            // This is the key difference from automatic mode!
            // ============================================================
            $this->command->info('Step 3.5: Manual Warehouse Receipt - confirming incoming stock...');
            $this->command->info('   → Simulating warehouse staff receiving and inspecting goods...');

            // In manual mode, we need to look for any pending pickings for this vendor bill
            // or create a stock move manually if none exists
            $incomingPicking = StockPicking::where('origin', 'VendorBill#'.$vendorBill->getKey())->first();

            if ($incomingPicking) {
                // Confirm each stock move in the picking using StockMoveService
                $stockMoveService = app(StockMoveService::class);
                foreach ($incomingPicking->stockMoves as $stockMove) {
                    $stockMoveService->confirmMove(new ConfirmStockMoveDTO(
                        stock_move_id: $stockMove->id,
                    ));
                }

                // Update picking state to Done
                $incomingPicking->update([
                    'state' => StockPickingState::Done,
                    'completed_at' => now(),
                ]);

                $this->command->info("   ✓ Incoming stock picking confirmed (ID: {$incomingPicking->id})");
                $this->command->info('   ✓ Stock moves confirmed - inventory journal entry created');
            } else {
                // In strict manual mode, no picking exists - we would create one manually
                // For this demo, we'll note that the goods are received without automated tracking
                $this->command->info('   ℹ No pending stock picking found - manual tracking required');
                $this->command->info('   (In production, warehouse staff would create a receipt manually)');
            }

            // ============================================================
            // STEP 4: Create Payment for Vendor Bill
            // ============================================================
            $this->command->info('Step 4: Creating payment for vendor bill...');

            $vendorPaymentDto = new CreatePaymentDTO(
                company_id: $company->id,
                journal_id: $cashJournalIqd->id,
                currency_id: $iqdCurrency->id,
                payment_date: now()->toDateString(),
                payment_type: PaymentType::Outbound,
                payment_method: PaymentMethod::Cash,
                paid_to_from_partner_id: $vendor->id,
                amount: null, // Will be calculated from document links
                document_links: [
                    new CreatePaymentDocumentLinkDTO(
                        document_type: 'vendor_bill',
                        document_id: $vendorBill->id,
                        amount_applied: $vendorBill->total_amount,
                    ),
                ],
                reference: 'SC3-PAY-VENDOR-001',
            );

            $vendorPayment = app(CreatePaymentAction::class)->execute($vendorPaymentDto, $user);
            $this->command->info("   ✓ Vendor payment created (ID: {$vendorPayment->id}, Amount: {$vendorPayment->amount})");

            // Confirm the payment
            app(PaymentService::class)->confirm($vendorPayment, $user);
            $vendorPayment->refresh();
            $this->command->info('   ✓ Vendor payment confirmed');

            // ============================================================
            // STEP 5: Create Sales Order for 5 items (with profit)
            // Note: In MANUAL mode, confirming SO creates a draft delivery picking
            // ============================================================
            $this->command->info('Step 5: Creating sales order for 5 GPU-RTX4090 with profit...');

            $salesUnitPrice = Money::of(3000000, 'IQD'); // 3,000,000 IQD per unit (20% profit)

            $salesOrderDto = new CreateSalesOrderDTO(
                company_id: $company->id,
                customer_id: $customer->id,
                currency_id: $iqdCurrency->id,
                created_by_user_id: $user->id,
                reference: 'SC3-SO-001',
                so_date: Carbon::now(),
                expected_delivery_date: Carbon::now()->addDays(3),
                exchange_rate_at_creation: 1.0,
                notes: 'Sales order for Scenario Three - Manual Inventory Workflow',
                lines: [
                    new CreateSalesOrderLineDTO(
                        product_id: $product->id,
                        description: $product->name,
                        quantity: 5,
                        unit_price: $salesUnitPrice,
                        tax_id: null,
                    ),
                ],
            );

            $salesOrder = app(CreateSalesOrderAction::class)->execute($salesOrderDto);
            $this->command->info("   ✓ Sales order created (ID: {$salesOrder->id}, Total: {$salesOrder->total_amount})");

            // Confirm the sales order
            app(ConfirmSalesOrderAction::class)->execute($salesOrder, $user);
            $salesOrder->refresh();
            $this->command->info("   ✓ Sales order confirmed (SO#: {$salesOrder->so_number})");
            $this->command->info('   → In MANUAL mode, delivery picking is created in DRAFT state');

            // ============================================================
            // STEP 5.5: Manual Delivery Confirmation
            // Warehouse staff physically ships the goods and confirms the delivery
            // This triggers the COGS journal entry
            // ============================================================
            $this->command->info('Step 5.5: Manual Delivery Confirmation - confirming outgoing shipment...');
            $this->command->info('   → Simulating warehouse staff packing and shipping goods...');

            // Find the delivery picking for this sales order
            $deliveryPicking = StockPicking::where('origin', 'LIKE', '%'.$salesOrder->so_number.'%')->first();

            if ($deliveryPicking) {
                // Verify it's in draft state (as expected in manual mode)
                if ($deliveryPicking->state === StockPickingState::Draft) {
                    $this->command->info("   ℹ Delivery picking found in DRAFT state (ID: {$deliveryPicking->id})");

                    // Confirm each stock move in the picking
                    $stockMoveService = app(StockMoveService::class);
                    foreach ($deliveryPicking->stockMoves as $stockMove) {
                        $stockMoveService->confirmMove(new ConfirmStockMoveDTO(
                            stock_move_id: $stockMove->id,
                        ));
                    }

                    // Update picking state to Done
                    $deliveryPicking->update([
                        'state' => StockPickingState::Done,
                        'completed_at' => now(),
                    ]);

                    $this->command->info("   ✓ Delivery picking confirmed (ID: {$deliveryPicking->id})");
                    $this->command->info('   ✓ Stock moves confirmed - COGS journal entry created');
                } else {
                    $this->command->info("   ℹ Delivery picking already in state: {$deliveryPicking->state->value}");
                }
            } else {
                $this->command->warn('   ⚠ No delivery picking found for sales order');
            }

            // ============================================================
            // STEP 6: Create Invoice from Sales Order and Post
            // ============================================================
            $this->command->info('Step 6: Creating and posting invoice from sales order...');

            $invoiceDto = new CreateInvoiceFromSalesOrderDTO(
                salesOrder: $salesOrder,
                invoice_date: Carbon::now(),
                due_date: Carbon::now()->addDays(15),
                default_income_account_id: $incomeAccount->id,
            );

            $invoice = app(CreateInvoiceFromSalesOrderAction::class)->execute($invoiceDto);
            $this->command->info("   ✓ Invoice created (ID: {$invoice->id}, Total: {$invoice->total_amount})");

            // Post the invoice
            app(InvoiceService::class)->confirm($invoice, $user);
            $invoice->refresh();
            $this->command->info("   ✓ Invoice posted (Invoice#: {$invoice->invoice_number})");

            // ============================================================
            // STEP 7: Create Payment for Invoice
            // ============================================================
            $this->command->info('Step 7: Creating payment for customer invoice...');

            $customerPaymentDto = new CreatePaymentDTO(
                company_id: $company->id,
                journal_id: $cashJournalIqd->id,
                currency_id: $iqdCurrency->id,
                payment_date: now()->toDateString(),
                payment_type: PaymentType::Inbound,
                payment_method: PaymentMethod::Cash,
                paid_to_from_partner_id: $customer->id,
                amount: null, // Will be calculated from document links
                document_links: [
                    new CreatePaymentDocumentLinkDTO(
                        document_type: 'invoice',
                        document_id: $invoice->id,
                        amount_applied: $invoice->total_amount,
                    ),
                ],
                reference: 'SC3-PAY-CUSTOMER-001',
            );

            $customerPayment = app(CreatePaymentAction::class)->execute($customerPaymentDto, $user);
            $this->command->info("   ✓ Customer payment created (ID: {$customerPayment->id}, Amount: {$customerPayment->amount})");

            // Confirm the payment
            app(PaymentService::class)->confirm($customerPayment, $user);
            $customerPayment->refresh();
            $this->command->info('   ✓ Customer payment confirmed');

            // ============================================================
            // Summary
            // ============================================================
            $this->command->info('');
            $this->command->info('=== Scenario Three Complete ===');
            $this->command->info('');
            $this->command->info('Summary (MANUAL Inventory Recording Mode):');
            $this->command->info('  • Inventory Mode: MANUAL_INVENTORY_RECORDING');
            $this->command->info('  • Opening Balance: 2,500,000 IQD (Loan to Cash)');
            $this->command->info("  • Purchase Order: {$purchaseOrder->po_number} - 10 units @ 2,500,000 IQD = 25,000,000 IQD");
            $this->command->info("  • Vendor Bill: {$vendorBill->bill_reference} - Paid");
            $this->command->info('  • Warehouse Receipt: Manually confirmed by warehouse staff');
            $this->command->info("  • Sales Order: {$salesOrder->so_number} - 5 units @ 3,000,000 IQD = 15,000,000 IQD");
            $this->command->info('  • Delivery: Manually confirmed by warehouse staff (COGS recorded)');
            $this->command->info("  • Invoice: {$invoice->invoice_number} - Paid");
            $this->command->info('  • Profit on sold items: 5 × 500,000 = 2,500,000 IQD');
            $this->command->info('');
            $this->command->info('Key Difference from Scenario Two (AUTO mode):');
            $this->command->info('  • Stock pickings required explicit confirmation');
            $this->command->info('  • Inventory/COGS entries created only after warehouse confirmation');
            $this->command->info('  • This separates purchasing/sales from physical warehouse operations');
        });
    }
}
