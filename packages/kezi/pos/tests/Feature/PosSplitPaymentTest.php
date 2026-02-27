<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Pos\Actions\SyncOrdersAction;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\DataTransferObjects\PosOrderLineData;
use Kezi\Pos\DataTransferObjects\PosOrderPaymentData;
use Kezi\Pos\Enums\PosOrderStatus;
use Kezi\Pos\Enums\PosSessionStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderPayment;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

// ════════════════════════════════════════════════════════════════════
// 1. Unit / DTO tests — no DB needed
// ════════════════════════════════════════════════════════════════════

describe('PosOrderPaymentData DTO', function () {
    it('parses a cash payment correctly', function () {
        $data = PosOrderPaymentData::from([
            'method' => 'cash',
            'amount' => 5000,
            'amount_tendered' => 6000,
            'change_given' => 1000,
        ]);

        expect($data->method)->toBe(PaymentMethod::Cash)
            ->and($data->amount)->toBe(5000)
            ->and($data->amount_tendered)->toBe(6000)
            ->and($data->change_given)->toBe(1000);
    });

    it('parses a card payment with defaults', function () {
        $data = PosOrderPaymentData::from([
            'method' => 'credit_card',
            'amount' => 3000,
        ]);

        expect($data->method)->toBe(PaymentMethod::CreditCard)
            ->and($data->amount)->toBe(3000)
            ->and($data->amount_tendered)->toBeNull()
            ->and($data->change_given)->toBe(0);
    });

    it('falls back to Cash for unknown method', function () {
        $data = PosOrderPaymentData::from([
            'method' => 'unknown_method',
            'amount' => 1000,
        ]);

        expect($data->method)->toBe(PaymentMethod::Cash);
    });
});

describe('PosOrderData DTO — split payment support', function () {
    it('defaults to empty payments collection when no payments key provided', function () {
        $data = PosOrderData::from([
            'uuid' => Str::uuid()->toString(),
            'order_number' => 'TEST-001',
            'status' => 'paid',
            'payment_method' => 'cash',
            'ordered_at' => now()->toIso8601String(),
            'total_amount' => '10000',
            'total_tax' => '0',
            'discount_amount' => '0',
            'currency_id' => 1,
            'pos_session_id' => 1,
        ]);

        expect($data->payments)->toBeEmpty()
            ->and($data->payment_method)->toBe(PaymentMethod::Cash);
    });

    it('parses a split payments array and derives primary payment_method', function () {
        $data = PosOrderData::from([
            'uuid' => Str::uuid()->toString(),
            'order_number' => 'TEST-002',
            'status' => 'paid',
            'ordered_at' => now()->toIso8601String(),
            'total_amount' => '10000',
            'total_tax' => '0',
            'discount_amount' => '0',
            'currency_id' => 1,
            'pos_session_id' => 1,
            'payments' => [
                ['method' => 'cash', 'amount' => 5000, 'amount_tendered' => 6000, 'change_given' => 1000],
                ['method' => 'credit_card', 'amount' => 5000],
            ],
        ]);

        expect($data->payments)->toHaveCount(2)
            ->and($data->payments->first()->method)->toBe(PaymentMethod::Cash)
            ->and($data->payments->last()->method)->toBe(PaymentMethod::CreditCard)
            ->and($data->payment_method)->toBe(PaymentMethod::Cash);
    });
});

// ════════════════════════════════════════════════════════════════════
// 2. SyncOrdersAction — persists PosOrderPayment rows
// ════════════════════════════════════════════════════════════════════

describe('SyncOrdersAction — split payment persistence', function () {
    uses(RefreshDatabase::class);

    it('creates PosOrderPayment rows for each split payment', function () {
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $company = Company::factory()->create(['currency_id' => $currency->id]);
        $user = User::factory()->create();
        $user->companies()->attach($company);

        $posProfile = PosProfile::factory()->create(['company_id' => $company->id]);
        $posSession = PosSession::factory()->create([
            'user_id' => $user->id,
            'pos_profile_id' => $posProfile->id,
            'company_id' => $company->id,
            'status' => PosSessionStatus::Opened,
        ]);

        $uuid = Str::uuid()->toString();
        $orderData = new PosOrderData(
            uuid: $uuid,
            order_number: 'ORD-SPLIT-001',
            status: PosOrderStatus::Paid,
            payment_method: PaymentMethod::Cash,
            ordered_at: now(),
            total_amount: '10000',
            total_tax: '0',
            discount_amount: '0',
            notes: null,
            customer_id: null,
            currency_id: $currency->id,
            pos_session_id: $posSession->id,
            sector_data: [],
            lines: collect([]),
            payments: PosOrderPaymentData::collect([
                ['method' => 'cash', 'amount' => 5000, 'amount_tendered' => 6000, 'change_given' => 1000],
                ['method' => 'credit_card', 'amount' => 5000],
            ]),
        );

        // We use the action but disable strict stock + won't check invoice creation
        \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = true;

        $action = app(SyncOrdersAction::class);
        $result = $action->execute(collect([$orderData]), $user, $company->id);

        \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = false;

        // Order must be created (the action isolates invoice failures in non-strict mode)
        $order = PosOrder::where('uuid', $uuid)->first();
        expect($order)->not->toBeNull();

        $payments = PosOrderPayment::where('pos_order_id', $order->id)->get();
        expect($payments)->toHaveCount(2);

        $cashPayment = $payments->firstWhere('payment_method', PaymentMethod::Cash);
        expect($cashPayment)->not->toBeNull()
            ->and($cashPayment->amount)->toBe(5000)
            ->and($cashPayment->amount_tendered)->toBe(6000)
            ->and($cashPayment->change_given)->toBe(1000);

        $cardPayment = $payments->firstWhere('payment_method', PaymentMethod::CreditCard);
        expect($cardPayment)->not->toBeNull()
            ->and($cardPayment->amount)->toBe(5000)
            ->and($cardPayment->amount_tendered)->toBeNull()
            ->and($cardPayment->change_given)->toBe(0);
    });

    it('synthesises a single payment row from legacy payment_method when no payments array given', function () {
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $company = Company::factory()->create(['currency_id' => $currency->id]);
        $user = User::factory()->create();
        $user->companies()->attach($company);

        $posProfile = PosProfile::factory()->create(['company_id' => $company->id]);
        $posSession = PosSession::factory()->create([
            'user_id' => $user->id,
            'pos_profile_id' => $posProfile->id,
            'company_id' => $company->id,
            'status' => PosSessionStatus::Opened,
        ]);

        $uuid = Str::uuid()->toString();
        $orderData = new PosOrderData(
            uuid: $uuid,
            order_number: 'ORD-LEGACY-001',
            status: PosOrderStatus::Paid,
            payment_method: PaymentMethod::Cash,
            ordered_at: now(),
            total_amount: '5000',
            total_tax: '0',
            discount_amount: '0',
            notes: null,
            customer_id: null,
            currency_id: $currency->id,
            pos_session_id: $posSession->id,
            sector_data: [],
            lines: collect([]),
            payments: collect([]), // empty — triggers backward-compat path
        );

        \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = true;
        app(SyncOrdersAction::class)->execute(collect([$orderData]), $user, $company->id);
        \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = false;

        $order = PosOrder::where('uuid', $uuid)->first();
        expect($order)->not->toBeNull();

        $payments = PosOrderPayment::where('pos_order_id', $order->id)->get();

        // Should have exactly 1 synthesised payment row
        expect($payments)->toHaveCount(1)
            ->and($payments->first()->payment_method)->toBe(PaymentMethod::Cash)
            ->and($payments->first()->amount)->toBe(5000);
    });
});

// ════════════════════════════════════════════════════════════════════
// 3. Accounting integration — split payment creates multiple Payment records
// Uses WithConfiguredCompany for full accounting stack setup
// ════════════════════════════════════════════════════════════════════

describe('CreateInvoiceFromPosOrderAction — split payment accounting', function () {
    uses(RefreshDatabase::class);
    uses(WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->currency = $this->company->currency;

        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => AccountType::Income,
            'name' => 'Sales Revenue',
            'code' => '4000',
        ]);

        $this->salesJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => JournalType::Sale,
            'name' => 'POS Sales Split',
        ]);

        $warehouse = StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Internal,
            'name' => 'Warehouse',
        ]);

        StockLocation::factory()->create([
            'company_id' => $this->company->id,
            'type' => StockLocationType::Customer,
            'name' => 'Customer',
        ]);

        $this->company = Company::where('id', $this->company->id)->first();
        $this->company->update([
            'default_sales_journal_id' => $this->salesJournal->id,
            'default_stock_location_id' => $warehouse->id,
        ]);

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
            'status' => PosSessionStatus::Opened,
            'company_id' => $this->company->id,
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
            'name' => 'Split Payment Test Product',
            'income_account_id' => $this->incomeAccount->id,
            'unit_price' => Money::of(100, $this->currency->code),
        ]);

        $this->seedStock($this->product, $warehouse, 100);
    });

    it('creates two Payment records when a split payment order is synced', function () {
        $uuid = Str::uuid()->toString();
        $orderData = new PosOrderData(
            uuid: $uuid,
            order_number: 'ORD-SPLIT-ACCT-001',
            status: PosOrderStatus::Paid,
            payment_method: PaymentMethod::Cash,
            ordered_at: now(),
            total_amount: '100000',
            total_tax: '0',
            discount_amount: '0',
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
            ]),
            payments: PosOrderPaymentData::collect([
                ['method' => 'cash', 'amount' => 30000],      // $30 cash
                ['method' => 'credit_card', 'amount' => 70000], // $70 card
            ]),
        );

        $result = app(SyncOrdersAction::class)->execute(collect([$orderData]), $this->user, $this->company->id);

        if (! empty($result['failed'])) {
            dump('Sync Failed:', $result['failed']);
        }

        expect($result['failed'])->toBeEmpty();

        $order = PosOrder::where('uuid', $uuid)->first();
        $invoice = Invoice::find($order->invoice_id);

        expect($invoice)->not->toBeNull()
            ->and($invoice->status)->toBe(InvoiceStatus::Paid);

        // There should be exactly 2 Payment records linked to the invoice
        $payments = $invoice->payments;
        expect($payments)->toHaveCount(2);

        // The sum of all payment amounts equals the invoice total
        $totalPaid = $payments->sum(fn ($p) => $p->amount->getMinorAmount()->toInt());
        expect($totalPaid)->toBe($invoice->total_amount->getMinorAmount()->toInt());

        // Verify distinct payment methods
        $methods = $payments->pluck('payment_method')->map(fn ($m) => $m->value)->sort()->values()->toArray();
        expect($methods)->toContain(PaymentMethod::Cash->value)
            ->and($methods)->toContain(PaymentMethod::CreditCard->value);
    });

    it('creates one Payment record for a legacy single-payment order (backward compatibility)', function () {
        $uuid = Str::uuid()->toString();
        $orderData = new PosOrderData(
            uuid: $uuid,
            order_number: 'ORD-LEGACY-ACCT-001',
            status: PosOrderStatus::Paid,
            payment_method: PaymentMethod::Cash,
            ordered_at: now(),
            total_amount: '100000',
            total_tax: '0',
            discount_amount: '0',
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
            ]),
            payments: collect([]), // No split payments — legacy path
        );

        $result = app(SyncOrdersAction::class)->execute(collect([$orderData]), $this->user, $this->company->id);

        expect($result['failed'])->toBeEmpty();

        $order = PosOrder::where('uuid', $uuid)->first();
        $invoice = Invoice::find($order->invoice_id);

        expect($invoice)->not->toBeNull()
            ->and($invoice->status)->toBe(InvoiceStatus::Paid)
            ->and($invoice->payments)->toHaveCount(1);
    });
});

// ════════════════════════════════════════════════════════════════════
// 4. HTTP API — validation accepts payments array
// ════════════════════════════════════════════════════════════════════

describe('OrderSyncController — split payment HTTP validation', function () {
    uses(RefreshDatabase::class);

    beforeEach(function () {
        $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        \Spatie\Permission\Models\Permission::findOrCreate('create_pos_order', 'web');
        setPermissionsTeamId($this->company->id);
        $this->user->givePermissionTo('create_pos_order');

        Sanctum::actingAs($this->user, ['*']);

        $this->profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
        $this->session = PosSession::factory()->create([
            'user_id' => $this->user->id,
            'pos_profile_id' => $this->profile->id,
            'company_id' => $this->company->id,
            'status' => 'opened',
        ]);
    });

    it('accepts an order with a split payments array and persists two payment rows', function () {
        $uuid = Str::uuid()->toString();

        postJson('/api/pos/sync/orders', [
            'orders' => [
                [
                    'uuid' => $uuid,
                    'order_number' => 'SPLIT-HTTP-001',
                    'status' => 'paid',
                    'ordered_at' => now()->toIso8601String(),
                    'total_amount' => '10000',
                    'total_tax' => '0',
                    'currency_id' => $this->currency->id,
                    'pos_session_id' => $this->session->id,
                    'lines' => [
                        [
                            'product_id' => 1,
                            'quantity' => 1,
                            'unit_price' => '10000',
                            'tax_amount' => '0',
                            'total_amount' => '10000',
                            'metadata' => [],
                        ],
                    ],
                    'payments' => [
                        ['method' => 'cash', 'amount' => 5000, 'amount_tendered' => 6000, 'change_given' => 1000],
                        ['method' => 'credit_card', 'amount' => 5000],
                    ],
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('failed', []);

        assertDatabaseHas('pos_orders', ['uuid' => $uuid]);
        assertDatabaseCount('pos_order_payments', 2);

        $order = PosOrder::where('uuid', $uuid)->first();
        $cashRow = PosOrderPayment::where('pos_order_id', $order->id)
            ->where('payment_method', 'cash')
            ->first();

        expect($cashRow)->not->toBeNull()
            ->and($cashRow->amount)->toBe(5000)
            ->and($cashRow->amount_tendered)->toBe(6000)
            ->and($cashRow->change_given)->toBe(1000);
    });

    it('accepts a legacy order with no payments array and creates one synthetic payment row', function () {
        $uuid = Str::uuid()->toString();

        postJson('/api/pos/sync/orders', [
            'orders' => [
                [
                    'uuid' => $uuid,
                    'order_number' => 'LEGACY-HTTP-001',
                    'status' => 'paid',
                    'payment_method' => 'cash',
                    'ordered_at' => now()->toIso8601String(),
                    'total_amount' => '5000',
                    'total_tax' => '0',
                    'currency_id' => $this->currency->id,
                    'pos_session_id' => $this->session->id,
                    'lines' => [
                        [
                            'product_id' => 1,
                            'quantity' => 1,
                            'unit_price' => '5000',
                            'tax_amount' => '0',
                            'total_amount' => '5000',
                            'metadata' => [],
                        ],
                    ],
                    // No 'payments' key — legacy path
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('failed', []);

        assertDatabaseHas('pos_orders', ['uuid' => $uuid]);
        assertDatabaseCount('pos_order_payments', 1);

        $order = PosOrder::where('uuid', $uuid)->first();
        $payment = PosOrderPayment::where('pos_order_id', $order->id)->first();

        expect($payment)->not->toBeNull()
            ->and($payment->payment_method)->toBe(PaymentMethod::Cash)
            ->and($payment->amount)->toBe(5000);
    });

    it('rejects orders with a payments entry missing the required amount field', function () {
        postJson('/api/pos/sync/orders', [
            'orders' => [
                [
                    'uuid' => Str::uuid()->toString(),
                    'order_number' => 'BAD-PAY-001',
                    'status' => 'paid',
                    'ordered_at' => now()->toIso8601String(),
                    'total_amount' => '10000',
                    'total_tax' => '0',
                    'currency_id' => $this->currency->id,
                    'pos_session_id' => $this->session->id,
                    'lines' => [],
                    'payments' => [
                        ['method' => 'cash'], // missing amount
                    ],
                ],
            ],
        ])
            ->assertUnprocessable();
    });
});
