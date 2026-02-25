<?php

use Illuminate\Support\Str;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\DataTransferObjects\PosOrderLineData;
use Kezi\Pos\Enums\PosOrderStatus;
use Kezi\Pos\Enums\PosSessionStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setUpWithConfiguredCompany();

    // Ensure we have USD and IQD
    $this->usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2]);
    $this->iqd = Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'decimal_places' => 3]);

    // Set company base currency to USD
    $this->company->update(['currency_id' => $this->usd->id]);
    $this->company->refresh();

    // Setup Accounting
    $this->incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Income,
        'name' => 'Sales Revenue',
    ]);

    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
        'name' => 'POS Sales',
    ]);

    $this->paymentJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Bank,
        'name' => 'Cash',
    ]);

    // Setup Inventory
    $warehouse = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'type' => StockLocationType::Internal,
    ]);

    // Setup POS Profile
    $this->posProfile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'stock_location_id' => $warehouse->id,
        'default_income_account_id' => $this->incomeAccount->id,
        'default_payment_journal_id' => $this->paymentJournal->id,
    ]);

    $this->posSession = PosSession::create([
        'pos_profile_id' => $this->posProfile->id,
        'user_id' => $this->user->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => PosSessionStatus::Opened,
        'company_id' => $this->company->id,
    ]);

    // Setup Products
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'name' => 'Test Product',
        'income_account_id' => $this->incomeAccount->id,
    ]);

    $this->seedStock($this->product, $warehouse, 100);
});

it('uses exchange rate 1.0 for same-currency POS orders', function () {
    $orderUuid = (string) Str::uuid();
    $orderData = new PosOrderData(
        uuid: $orderUuid,
        order_number: 'POS-SC-001',
        status: PosOrderStatus::Paid,
        payment_method: \Kezi\Payment\Enums\Payments\PaymentMethod::Cash,
        ordered_at: now(),
        total_amount: 5000, // 50.00 USD in minor units
        total_tax: 0,
        discount_amount: 0,
        notes: 'Same-currency order',
        customer_id: null,
        currency_id: $this->usd->id,
        pos_session_id: $this->posSession->id,
        sector_data: [],
        lines: collect([
            new PosOrderLineData(
                product_id: $this->product->id,
                quantity: 1,
                unit_price: 5000,
                discount_amount: 0,
                tax_amount: 0,
                total_amount: 5000,
                metadata: []
            ),
        ]),
        payments: collect([])
    );

    app(SyncOrdersAction::class)->execute(collect([$orderData]), $this->user, $this->company->id);

    $posOrder = PosOrder::where('uuid', $orderUuid)->firstOrFail();
    $invoice = $posOrder->invoice;
    expect($invoice)->not->toBeNull();

    // Same currency → exchange rate should be 1.0
    expect((float) $invoice->exchange_rate_at_creation)->toBe(1.0);

    // Journal entry lines should have exchange_rate_at_transaction = 1.0
    $journalEntry = $invoice->journalEntry;
    expect($journalEntry)->not->toBeNull();

    foreach ($journalEntry->lines as $line) {
        expect((float) $line->exchange_rate_at_transaction)->toBe(1.0);
    }
});

it('uses the real exchange rate for multi-currency POS orders', function () {
    // Arrange: Set exchange rate. In this system the rate represents
    // "1 foreign currency unit = X base currency units".
    // So for IQD→USD: 1 IQD = 0.00066666 USD
    $rateValue = 0.00066666;
    CurrencyRate::create([
        'currency_id' => $this->iqd->id,
        'company_id' => $this->company->id,
        'rate' => $rateValue,
        'effective_date' => now()->startOfDay(),
    ]);

    $orderUuid = (string) Str::uuid();
    // 1500.000 IQD (3 decimal places → minor = 1500000)
    $orderData = new PosOrderData(
        uuid: $orderUuid,
        order_number: 'POS-MC-001',
        status: PosOrderStatus::Paid,
        payment_method: \Kezi\Payment\Enums\Payments\PaymentMethod::Cash,
        ordered_at: now(),
        total_amount: 1500000,
        total_tax: 0,
        discount_amount: 0,
        notes: 'Multi-currency order',
        customer_id: null,
        currency_id: $this->iqd->id,
        pos_session_id: $this->posSession->id,
        sector_data: [],
        lines: collect([
            new PosOrderLineData(
                product_id: $this->product->id,
                quantity: 1,
                unit_price: 1500000,
                discount_amount: 0,
                tax_amount: 0,
                total_amount: 1500000,
                metadata: []
            ),
        ]),
        payments: collect([])
    );

    // Act
    app(SyncOrdersAction::class)->execute(collect([$orderData]), $this->user, $this->company->id);

    // Assert
    $posOrder = PosOrder::where('uuid', $orderUuid)->firstOrFail();
    $invoice = $posOrder->invoice;
    expect($invoice)->not->toBeNull();

    // Verify exchange rate on invoice
    expect((float) $invoice->exchange_rate_at_creation)->toBe($rateValue);

    // Verify journal entry lines have the correct exchange rate
    $journalEntry = $invoice->journalEntry;
    expect($journalEntry)->not->toBeNull();

    // All lines in a multi-currency entry should carry the exchange rate used
    foreach ($journalEntry->lines as $line) {
        expect((float) $line->exchange_rate_at_transaction)->toBe($rateValue);
    }
});
