<?php

declare(strict_types=1);

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('creates a vendor bill line with valid data (Happy Path)', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Accounting\Models\Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = Currency::factory()->create(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Product\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $company->id,
    ]);

    $dto = new CreateVendorBillLineDTO(
        product_id: $product->id,
        description: 'Test Product Description',
        quantity: 10,
        unit_price: Money::of(150, 'USD'),
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);
    $line = $action->execute($vendorBill, $dto);

    expect($line)->toBeInstanceOf(VendorBillLine::class)
        ->and($line->product_id)->toBe($product->id)
        ->and($line->quantity)->toBe('10.00')
        ->and($line->unit_price->getAmount()->toFloat())->toBe(150.0)
        ->and($line->subtotal->getAmount()->toFloat())->toBe(1500.0)
        ->and($line->total_line_tax->getAmount()->toFloat())->toBe(0.0);

    \Pest\Laravel\assertDatabaseHas('vendor_bill_lines', [
        'id' => $line->id,
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'expense_account_id' => $expenseAccount->id,
    ]);
});

it('updates parent vendor bill totals after line creation', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = Currency::factory()->create(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'total_amount' => Money::of(0, 'USD'),
        'total_tax' => Money::of(0, 'USD'),
    ]);

    /** @var \Kezi\Accounting\Models\Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    $dto = new CreateVendorBillLineDTO(
        product_id: null,
        description: 'Service Line',
        quantity: 2,
        unit_price: Money::of(100, 'USD'),
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);
    $action->execute($vendorBill, $dto);

    $vendorBill->refresh();

    // 2 * 100 = 200
    expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(200.0)
        ->and($vendorBill->total_tax->getAmount()->toFloat())->toBe(0.0);
});

it('calculates line and parent tax correctly', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $currency */
    $currency = Currency::factory()->create(['code' => 'USD']);

    /** @var \Kezi\Purchase\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var \Kezi\Accounting\Models\Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    /** @var \Kezi\Accounting\Models\Tax $tax */
    $tax = Tax::factory()->create([
        'company_id' => $company->id,
        'rate' => 10.0, // 10%
    ]);

    $dto = new CreateVendorBillLineDTO(
        product_id: null,
        description: 'Taxed Service',
        quantity: 5,
        unit_price: Money::of(100, 'USD'),
        expense_account_id: $expenseAccount->id,
        tax_id: $tax->id,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);
    $line = $action->execute($vendorBill, $dto);

    // Line Subtotal: 5 * 100 = 500
    // Line Tax: 500 * 0.10 = 50
    expect($line->subtotal->getAmount()->toFloat())->toBe(500.0)
        ->and($line->total_line_tax->getAmount()->toFloat())->toBe(50.0);

    $vendorBill->refresh();
    // Vendor Bill Totals: Amount = 500 + 50 = 550, Tax = 50
    expect($vendorBill->total_amount->getAmount()->toFloat())->toBe(550.0)
        ->and($vendorBill->total_tax->getAmount()->toFloat())->toBe(50.0);
});

it('handles different currencies between Bill and DTO unit price', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var \Kezi\Foundation\Models\Currency $usd */
    $usd = Currency::factory()->create(['code' => 'USD']);

    /** @var \Kezi\Foundation\Models\Currency $eur */
    $eur = Currency::factory()->create(['code' => 'EUR']);

    /** @var \Kezi\Purchase\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $eur->id, // Bill is in EUR
    ]);

    /** @var \Kezi\Accounting\Models\Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    // DTO provides numeric unit price. It should be cast to Bill currency (EUR).
    $dto = new CreateVendorBillLineDTO(
        product_id: null,
        description: 'EUR Line from Numeric',
        quantity: 1,
        unit_price: '100.00',
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);
    $line = $action->execute($vendorBill, $dto);

    expect($line->unit_price->getCurrency()->getCurrencyCode())->toBe('EUR')
        ->and($line->unit_price->getAmount()->toFloat())->toBe(100.0);
});

it('handles zero quantity or price correctly', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    $currency = Currency::factory()->create(['code' => 'USD']);
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);
    /** @var Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);
    $expenseAccountId = (int) $expenseAccount->id;

    $action = app(CreateVendorBillLineAction::class);

    $dtoZeroQty = new CreateVendorBillLineDTO(
        product_id: null,
        description: 'Zero Qty',
        quantity: 0,
        unit_price: Money::of(100, 'USD'),
        expense_account_id: $expenseAccountId,
        tax_id: null,
        analytic_account_id: null
    );
    $lineZeroQty = $action->execute($vendorBill, $dtoZeroQty);
    expect($lineZeroQty->subtotal->isZero())->toBeTrue();

    // Zero Price
    $dtoZeroPrice = new CreateVendorBillLineDTO(
        product_id: null,
        description: 'Zero Price',
        quantity: 10,
        unit_price: Money::of(0, 'USD'),
        expense_account_id: $expenseAccountId,
        tax_id: null,
        analytic_account_id: null
    );
    $lineZeroPrice = $action->execute($vendorBill, $dtoZeroPrice);
    expect($lineZeroPrice->subtotal->isZero())->toBeTrue();
});

it('ensures relationship integrity', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var Currency $currency */
    $currency = Currency::factory()->create(['code' => 'USD']);

    /** @var VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create(['company_id' => $company->id]);

    /** @var Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    $dto = new CreateVendorBillLineDTO(
        product_id: $product->id,
        description: 'Integrity Test',
        quantity: 1,
        unit_price: Money::of(50, 'USD'),
        expense_account_id: (int) $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);
    $line = $action->execute($vendorBill, $dto);

    /** @var Product $lineProduct */
    $lineProduct = $line->product;

    expect($line->vendorBill->is($vendorBill))->toBeTrue()
        ->and($lineProduct->is($product))->toBeTrue()
        ->and($line->expenseAccount->is($expenseAccount))->toBeTrue();
});

it('throws exception when using a template product', function () {
    /** @var \Tests\TestCase $this */
    /** @var \App\Models\Company $company */
    /** @phpstan-ignore-next-line */
    $company = $this->company;

    /** @var Currency $currency */
    $currency = Currency::factory()->create(['code' => 'USD']);

    /** @var VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
    ]);

    /** @var Account $expenseAccount */
    $expenseAccount = Account::factory()->create(['company_id' => $company->id]);

    /** @var Product $templateProduct */
    $templateProduct = Product::factory()->create([
        'company_id' => $company->id,
        'is_template' => true,
    ]);

    $dto = new CreateVendorBillLineDTO(
        product_id: $templateProduct->id,
        description: 'Template Product Line',
        quantity: 1,
        unit_price: Money::of(100, 'USD'),
        expense_account_id: (int) $expenseAccount->id,
        tax_id: null,
        analytic_account_id: null
    );

    /** @var CreateVendorBillLineAction $action */
    $action = app(CreateVendorBillLineAction::class);

    expect(fn () => $action->execute($vendorBill, $dto))
        ->toThrow(\InvalidArgumentException::class, 'Cannot create vendor bill lines for template products');
});
