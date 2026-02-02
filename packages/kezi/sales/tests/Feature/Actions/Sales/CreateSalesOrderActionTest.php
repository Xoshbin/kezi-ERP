<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Product\Models\Product;
use Kezi\Sales\Actions\Sales\CreateSalesOrderAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateSalesOrderAction::class);
});

it('creates a sales order with lines and calculates totals', function () {
    $customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    $location = StockLocation::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreateSalesOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 10,
        unit_price: Money::of(100, $currency->code),
        tax_id: null,
        expected_delivery_date: now()->addDays(7)
    );

    $dto = new CreateSalesOrderDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $currency->id,
        created_by_user_id: $this->user->id,
        reference: 'SO-TEST-001',
        so_date: now(),
        expected_delivery_date: now()->addDays(7),
        exchange_rate_at_creation: 1.0,
        notes: 'Test notes',
        terms_and_conditions: 'Test terms',
        delivery_location_id: $location->id,
        incoterm: \Kezi\Foundation\Enums\Incoterm::Cif,
        lines: [$lineDto]
    );

    $salesOrder = $this->action->execute($dto);

    expect($salesOrder)->not->toBeNull();
    expect($salesOrder->reference)->toBe('SO-TEST-001');
    expect($salesOrder->total_amount->getAmount()->toFloat())->toBe(1000.0);
    expect($salesOrder->lines)->toHaveCount(1);
    expect($salesOrder->lines->first()->subtotal->getAmount()->toFloat())->toBe(1000.0);
});

it('respects lock date service', function () {
    // Set a lock date in the past
    $lockDate = now()->addDay();
    \Kezi\Accounting\Models\LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Kezi\Accounting\Enums\Accounting\LockDateType::HardLock,
        'locked_until' => $lockDate,
    ]);

    $customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->createSafely(['code' => 'USD']);

    $dto = new CreateSalesOrderDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $currency->id,
        created_by_user_id: $this->user->id,
        reference: 'SO-TEST-LOCK',
        so_date: now(),
        expected_delivery_date: now()->addDays(7),
        exchange_rate_at_creation: 1.0,
        lines: []
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Kezi\Accounting\Exceptions\PeriodIsLockedException::class);
});
