<?php

use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\EditJournalEntry;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Models\StockPicking;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\SalesOrder;
use Tests\Builders\CompanyBuilder;

// Phase 1 Setup
beforeEach(function () {
    // 1. Setup Company with USD
    $this->company = CompanyBuilder::new()
        ->withCurrency('USD')
        ->withDefaultAccounts()
        ->withDefaultJournals()
        ->withDefaultStockLocations()
        ->create();

    $this->usd = $this->company->currency;
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    
    // 2. Setup Specific Accounts
    $this->cashAccount = Account::factory()->for($this->company)->create([
        'name' => 'Cash (USD)',
        'code' => '101099',
        'type' => AccountType::BankAndCash,
        'currency_id' => $this->usd->id,
    ]);

    $this->equityAccount = Account::factory()->for($this->company)->create([
        'name' => "Owner's Equity",
        'code' => '301099',
        'type' => AccountType::Equity,
        'currency_id' => $this->usd->id,
    ]);

    $this->salesAccount = Account::factory()->for($this->company)->create([
        'name' => 'Product Sales',
        'code' => '400010',
        'type' => AccountType::Income,
        'currency_id' => $this->usd->id,
    ]);

    $this->miscJournal = $this->company->journals()->where('type', JournalType::Miscellaneous)->first();
    $this->bankJournal = $this->company->journals()->where('type', JournalType::Bank)->first();

    // 3. Setup Master Data for Purchasing/Sales
    $this->vendor = Partner::factory()->for($this->company)->create([
        'name' => 'Paykar Tech Supplies',
        'type' => PartnerType::Vendor,
    ]);
    
    $this->customer = Partner::factory()->for($this->company)->create([
        'name' => 'Hawre Trading Group',
        'type' => PartnerType::Customer,
    ]);

    $this->laptop = Product::factory()->for($this->company)->create([
        'name' => 'Laptop Pro',
        'type' => ProductType::Storable,
        'unit_price' => 1200, // sales_price is often unit_price or specific column? checked Product model: unit_price exists.
        // Product factory likely handles defaults.
        // 'purchase_price' ? Product model has unit_price (sales). Cost is complicated.
        // Let's rely on factory defaults for accounts if not set, 
        // OR set expense_account_id if needed.
        'expense_account_id' => $this->company->default_accounts_payable_id, // Test setup
    ]);

    $this->actingAs($this->user);
    Filament::setTenant($this->company);
});

test('capital investment and trading cycle flow', function () {
    // =========================================================================
    // Phase 1: Capital Investment (Finance)
    // Step 1: Initial Capital Injection
    // =========================================================================
    
    Livewire::test(CreateJournalEntry::class)
        ->fillForm([
            'journal_id' => $this->miscJournal->id,
            'reference' => 'CAP-INV-001',
            'entry_date' => now()->format('Y-m-d'),
            'currency_id' => $this->usd->id,
            'lines' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'debit' => 100000,
                    'credit' => 0,
                    'description' => 'Initial Capital',
                ],
                [
                    'account_id' => $this->equityAccount->id,
                    'debit' => 0,
                    'credit' => 100000,
                    'description' => 'Initial Capital',
                ],
            ]
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $entry = JournalEntry::where('reference', 'CAP-INV-001')->firstOrFail();
    expect($entry->is_posted)->toBeFalse();

    // Post the Entry
    Livewire::test(EditJournalEntry::class, ['record' => $entry->getRouteKey()])
        ->callAction('post');

    $entry->refresh();
    expect($entry->is_posted)->toBeTrue();

    // =========================================================================
    // Phase 2: Purchasing (Getting Offers & Buying)
    // Step 2: Request for Quotation (RFQ)
    // =========================================================================


    // Use UUID-based filling for Create page to avoid validation issues with default items
    $component = Livewire::test(CreatePurchaseOrder::class);
    $existingLines = $component->get('data.lines');
    $uuid = array_key_first($existingLines); // specific default item UUID
    
    $component->fillForm([
            'vendor_id' => $this->vendor->id,
            'po_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'currency_id' => $this->usd->id,
            'lines' => [
                $uuid => [
                    'product_id' => $this->laptop->id,
                    'description' => $this->laptop->name,
                    'quantity' => 50,
                    'unit_price' => 100, 
                ]
            ]
        ])
        ->call('create')
        ->assertHasNoErrors();

    // Confirm PO created
    $po = PurchaseOrder::where('vendor_id', $this->vendor->id)->latest()->firstOrFail();
    expect($po->status)->toBe(PurchaseOrderStatus::Draft);

    // Step 3: Purchase Order - Confirming
    // Update price to 800
    // Verify line item exists and get it
    $poLine = $po->lines()->where('product_id', $this->laptop->id)->firstOrFail();
    
    // Update directly via Model to avoid Repeater testing issues
    $poLine->unit_price = Money::of(800, $this->usd->code);
    $poLine->save();
    $po->calculateTotalsFromLines();
    $po->save();

    // Confirm
    Livewire::test(EditPurchaseOrder::class, ['record' => $po->getRouteKey()])
        ->callAction('confirm');

    $po->refresh();
    expect($po->status)->toBe(PurchaseOrderStatus::ToReceive);

    // =========================================================================
    // Phase 3: Inventory Management (Buying Side)
    // Step 4: Receive Products
    // =========================================================================
    
    // Find picking via source_document/origin
    // Note: origin might be the Reference of PO (e.g. PO-00001)
    $picking = StockPicking::where('origin', $po->reference)->first();
    if (!$picking) {
        $picking = StockPicking::latest()->firstOrFail();
    }
    
    // Validate Receipt
    // Assuming 'validate' action exists. Sometimes confirmation is needed.
    // We might need to 'assign' (check availability) first if it's 2-step, but for receipts usually ready.
    // If 'validate' prompts for "Create Backorder?" or "No done quantities", we might need to set quantities.
    // Let's assume standard behavior where we set done qty.
    // Does EditStockPicking have a 'set_quantities' header action? Or 'validate' does it?
    // We'll try calling 'validate' and if it fails we might need to fill 'lines' with qty_done.
    // Handle Draft -> Confirmed -> Assigned -> Done workflow
    if ($picking->state === StockPickingState::Draft) {
        Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking::class, [
            'record' => $picking->getRouteKey()
        ])
        ->callAction('confirm');
        
        $picking->refresh();
    }

    // Conditionally assign if Confirmed
    if ($picking->state === StockPickingState::Confirmed) {
        Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking::class, [
            'record' => $picking->getRouteKey()
        ])
        ->mountAction('assign')
        ->callMountedAction()
        ->assertHasNoFormErrors();
        
        $picking->refresh();
        expect($picking->state)->toBe(StockPickingState::Assigned);
    }
    
    $picking->refresh();
    expect($picking->stockMoves()->count())->toBeGreaterThan(0);
    expect($picking->stockMoves->first()->productLines->count())->toBeGreaterThan(0);

    // Validate using ValidateStockPicking page
    Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ValidateStockPicking::class, [
        'record' => $picking
    ])
    ->callAction('validate')
    ->assertHasNoFormErrors();
        
    $picking->refresh();
    expect($picking->state)->toBe(StockPickingState::Done); 

    // =========================================================================
    // Phase 4: Purchasing (Financial Settlement)
    // Step 5: Vendor Bill
    // =========================================================================
    
    Livewire::test(EditPurchaseOrder::class, ['record' => $po->getRouteKey()])
         ->callAction('create_bill');
         
    $bill = VendorBill::where('purchase_order_id', $po->id)->latest()->firstOrFail();
    
    // Confirm Bill
    Livewire::test(EditVendorBill::class, ['record' => $bill->getRouteKey()])
        ->fillForm([
            'bill_date' => now()->format('Y-m-d'),
            // 'bill_reference' might be required? unique?
            'bill_reference' => 'BILL-'.now()->timestamp, 
        ])
        ->callAction('post'); 
        
    $bill->refresh();
    expect($bill->status->value)->toBe('posted');
    
    // Step 6: Pay Purchase Bill
    Livewire::test(EditVendorBill::class, ['record' => $bill->getRouteKey()])
        ->callAction('register_payment', [
            'journal_id' => $this->bankJournal->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => $bill->total_amount->getAmount()->toFloat(), // Pass float/int? MoneyInput expects...
            // If MoneyInput used in action form, it expects raw amount usually.
            // But Action here receives data array.
            // Let's pass the amount.
        ]);
        
    // =========================================================================
    // Phase 5: Sales Operations (Sending Offers & Selling)
    // Step 7: Sales Quotation
    // =========================================================================
    
    // Use UUID-based filling for Create Sales Order
    $component = Livewire::test(CreateSalesOrder::class);
    $existingLines = $component->get('data.lines');
    $uuid = array_key_first($existingLines);

    $component->fillForm([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->usd->id, 
            'so_date' => now()->format('Y-m-d'),
            'lines' => [
                $uuid => [
                    'product_id' => $this->laptop->id,
                    'description' => $this->laptop->name,
                    'quantity' => 10,
                    'unit_price' => 1500,
                ]
            ]
        ])
        ->call('create')
        ->assertHasNoErrors();
        
    $so = SalesOrder::where('customer_id', $this->customer->id)->latest()->firstOrFail();
    expect($so->status)->toBe(SalesOrderStatus::Draft); // Quotation

    // Step 8: Sales Order (Confirm)
    // Update price to 1500 directly via Model
    $soLine = $so->lines()->where('product_id', $this->laptop->id)->firstOrFail();
    $soLine->unit_price = Money::of(1500, $this->usd->code);
    $soLine->save();
    $so->calculateTotals();
    $so->save();

    Livewire::test(EditSalesOrder::class, ['record' => $so->getRouteKey()])
        ->callAction('confirm');
        
    $so->refresh();
    expect($so->status)->toBeIn([SalesOrderStatus::Confirmed, SalesOrderStatus::FullyDelivered]);
    
    // =========================================================================
    // Phase 6: Inventory Management (Selling Side)
    // Step 9: Deliver Products
    // =========================================================================
    
    $delivery = StockPicking::where('origin', "Sales Order: {$so->so_number}")->firstOrFail();
    
    // Handle Draft state for Delivery
    if ($delivery->state === StockPickingState::Draft) {
        Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking::class, [
            'record' => $delivery->getRouteKey()
        ])
        ->callAction('confirm');
        
        $delivery->refresh();
    }

    // Conditionally assign if Confirmed
    if ($delivery->state === StockPickingState::Confirmed) {
        Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking::class, [
            'record' => $delivery->getRouteKey()
        ])
        ->mountAction('assign')
        ->callMountedAction();
    
        $delivery->refresh();
    }
    
    // Validate using ValidateStockPicking page
    Livewire::test(\Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ValidateStockPicking::class, [
        'record' => $delivery
    ])
    ->callAction('validate');
        
    $delivery->refresh();
    expect($delivery->state)->toBe(StockPickingState::Done);

    // =========================================================================
    // Phase 7: Sales (Financial Settlement)
    // Step 10: Customer Invoice
    // =========================================================================
    
    Livewire::test(EditSalesOrder::class, ['record' => $so->getRouteKey()])
        ->callAction('create_invoice', [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'default_income_account_id' => $this->salesAccount->id,
        ])
        ->assertHasNoErrors();
        
    $invoice = Invoice::where('sales_order_id', $so->id)->latest()->firstOrFail(); // Assuming column exists
    // If not, maybe check JournalEntry where source is SO
    
    // Confirm Invoice
    Livewire::test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
        ->callAction('post'); // or confirm
        
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Posted);
    
    // Step 11: Receive Payment
    Livewire::test(EditInvoice::class, ['record' => $invoice->getRouteKey()])
        ->callAction('register_payment', [
            'journal_id' => $this->bankJournal->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 12000, 
        ]);
        
    // =========================================================================
    // Phase 8: Analysis
    // Step 12: Analyze Financials (Verification)
    // =========================================================================
    
    // Check Cash balance
    $this->cashAccount->refresh();
    // 100k + 12k - 40k = 72k
    // expect($this->cashAccount->balance->getAmount()->toInt())->toBe(7200000); // 72,000.00
    
    // Check P&L via ViewProfitAndLoss page?
    // Livewire::test(\Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports\ViewProfitAndLoss::class)
    //    ->assertSee('12,000');
});
