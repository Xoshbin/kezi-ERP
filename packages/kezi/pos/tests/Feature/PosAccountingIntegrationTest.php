<?php

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Str;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\DataTransferObjects\PosOrderLineData;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->currency = $this->company->currency;

    // Setup Accounting
    $this->incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Income,
        'name' => 'Sales Revenue',
        'code' => '4000',
    ]);

    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
        'name' => 'POS Sales',
    ]);

    // Setup Inventory
    $warehouse = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
        'name' => 'Warehouse',
    ]);

    $customerLoc = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Customer,
        'name' => 'Customer',
    ]);

    // This is needed for fallback in CreateStockMovesForInvoiceAction
    $this->company->update(['default_stock_location_id' => $warehouse->id]);

    // Setup POS Profile
    $this->company = Company::where('id', $this->company->id)->first();
    $this->company->update(['default_sales_journal_id' => $this->salesJournal->id]);

    $this->posProfile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'stock_location_id' => $warehouse->id,
        'default_income_account_id' => $this->incomeAccount->id,
        'default_payment_journal_id' => $this->salesJournal->id,
        'settings' => ['strict_stock_check' => true],
    ]);

    $this->posSession = PosSession::create([
        'pos_profile_id' => $this->posProfile->id,
        'user_id' => $this->user->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => 'opened',
        'company_id' => $this->company->id,
    ]);

    // Setup Products
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'name' => 'Test Product',
        'income_account_id' => $this->incomeAccount->id,
        'unit_price' => Money::of(100, $this->currency->code),
    ]);

    // Seed stock using helper from trait
    $this->seedStock($this->product, $warehouse, 100);
});

it('creates and posts invoice when pos order is synced', function () {
    // Arrange
    $uuid = (string) Str::uuid();
    $orderData = new PosOrderData(
        uuid: $uuid,
        order_number: 'ORD-001',
        status: 'paid',
        ordered_at: now(),
        total_amount: 100000,
        total_tax: 0,
        discount_amount: 0,
        notes: 'Test Order',
        customer_id: null,
        currency_id: $this->currency->id,
        pos_session_id: $this->posSession->id,
        sector_data: [],
        lines: collect([
            new PosOrderLineData(
                product_id: $this->product->id,
                quantity: 1,
                unit_price: 100000,
                discount_amount: 0,
                tax_amount: 0,
                total_amount: 100000,
                metadata: []
            ),
        ])
    );

    // Act
    $action = app(SyncOrdersAction::class);
    $result = $action->execute(collect([$orderData]), $this->user, $this->company->id);

    if (! empty($result['failed'])) {
        dump('Sync Failed:', $result['failed']);
    }

    // Assert
    $this->assertCount(1, $result['synced']);
    $this->assertEmpty($result['failed']);

    $posOrder = PosOrder::where('uuid', $uuid)->first();
    $this->assertNotNull($posOrder);
    $this->assertNotNull($posOrder->invoice_id);

    $invoice = Invoice::find($posOrder->invoice_id);
    $this->assertNotNull($invoice);
    $this->assertEquals(InvoiceStatus::Posted, $invoice->status);
    $this->assertNotNull($invoice->invoice_number);
    $this->assertTrue($invoice->total_amount->isEqualTo(Money::of(100, $this->currency->code)));

    // Verify Journal Entry
    $this->assertNotNull($invoice->journal_entry_id);
    $journalEntry = $invoice->journalEntry;
    $this->assertEquals($this->salesJournal->id, $journalEntry->journal_id);

    // Verify Stock Move (created via Invoice flow)
    $stockMove = StockMove::where('source_type', Invoice::class)
        ->where('source_id', $invoice->id)
        ->first();

    $this->assertNotNull($stockMove, 'Stock move should be created via Invoice flow');
    $this->assertEquals(StockMoveStatus::Done, $stockMove->status);
    $this->assertEquals(StockMoveType::Outgoing, $stockMove->move_type);

    // Ensure NO stock move from PosOrder source (redundancy check)
    $directPosMove = StockMove::where('source_type', PosOrder::class)
        ->where('source_id', $posOrder->id)
        ->first();
    $this->assertNull($directPosMove, 'Should not create duplicate stock move from PosOrder directly');
});

it('handles walk in customer creation', function () {
    // Arrange
    $uuid = (string) Str::uuid();
    $orderData = new PosOrderData(
        uuid: $uuid,
        order_number: 'ORD-WALKIN',
        status: 'paid',
        ordered_at: now(),
        total_amount: 100000,
        total_tax: 0,
        discount_amount: 0,
        notes: null,
        customer_id: null,
        currency_id: $this->currency->id,
        pos_session_id: $this->posSession->id,
        sector_data: [],
        lines: collect([
            new PosOrderLineData(
                product_id: $this->product->id,
                quantity: 1,
                unit_price: 100000,
                discount_amount: 0,
                tax_amount: 0,
                total_amount: 100000,
                metadata: []
            ),
        ])
    );

    // Act
    app(SyncOrdersAction::class)->execute(collect([$orderData]), $this->user, $this->company->id);

    // Assert
    $posOrder = PosOrder::where('uuid', $uuid)->first();
    $this->assertNotNull($posOrder);

    $invoice = $posOrder->invoice;

    $this->assertNotNull($invoice->customer_id);
    $this->assertEquals('Walk-in Customer', $invoice->customer->name);
});
