<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\Enums\Accounting\LockDateType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\LockDate;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Models\SalesOrder;
use Kezi\Sales\Models\SalesOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateInvoiceFromSalesOrderAction::class);
});

it('can create an invoice from a confirmed sales order', function () {
    $currency = \Kezi\Foundation\Models\Currency::where('code', 'USD')->first() ?? \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    $customer = \Kezi\Foundation\Models\Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => PartnerType::Customer,
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
        'code' => '4000',
        'name' => 'Sales',
    ]);

    $so = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'status' => SalesOrderStatus::Confirmed,
        'currency_id' => $currency->id,
        'so_date' => now(),
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $this->company->id,
        'income_account_id' => $incomeAccount->id,
    ]);

    $line1 = SalesOrderLine::factory()->create([
        'sales_order_id' => $so->id,
        // 'company_id' removed as it doesn't exist on lines
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, 'USD'),
        'quantity_invoiced' => 0,
    ]);

    $dto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $so,
        invoice_date: now(),
        due_date: now()->addDays(30),
        default_income_account_id: $incomeAccount->id,
        fiscal_position_id: null,
        payment_term_id: null
    );

    $invoice = $this->action->execute($dto);

    expect($invoice)->toBeInstanceOf(\Kezi\Sales\Models\Invoice::class)
        ->company_id->toBe($this->company->id)
        ->customer_id->toBe($customer->id)
        ->sales_order_id->toBe($so->id)
        ->status->toBe(InvoiceStatus::Draft)
        ->invoice_date->toDateString()->toBe(now()->toDateString());

    expect($invoice->invoiceLines)->toHaveCount(1);
    $invLine = $invoice->invoiceLines->first();
    expect($invLine->product_id)->toBe($product->id);
    expect((float) $invLine->quantity)->toBe(10.0);
    expect($invLine->unit_price->getAmount()->toFloat())->toBe(100.0);

    // Check SO status updates
    $so->refresh();
    expect($so->status)->toBe(SalesOrderStatus::FullyInvoiced);

    // Check line status updates
    $line1->refresh();
    expect($line1->quantity_invoiced)->toBe(10.0);
});

it('prevents creation if sales order is not in a valid state', function () {
    $so = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Draft, // Draft cannot be invoiced
    ]);

    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);

    $dto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $so,
        invoice_date: now(),
        due_date: now()->addDays(30),
        default_income_account_id: $account->id
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(ValidationException::class);
});

it('prevents creation if invoice already exists', function () {
    $currency = \Kezi\Foundation\Models\Currency::where('code', 'USD')->first() ?? \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    $so = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'currency_id' => $currency->id,
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create(['company_id' => $this->company->id]);
    SalesOrderLine::factory()->create([
        'sales_order_id' => $so->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, 'USD'),
    ]);

    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);

    $dto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $so,
        invoice_date: now(),
        due_date: now()->addDays(30),
        default_income_account_id: $account->id
    );

    // First execution succeeds
    $this->action->execute($dto);

    // Second execution fails due to "hasInvoices" check
    expect(fn () => $this->action->execute($dto))
        ->toThrow(ValidationException::class, 'This sales order cannot be invoiced in its current status');
});

it('enforces lock date', function () {
    $currency = \Kezi\Foundation\Models\Currency::where('code', 'USD')->first() ?? \Kezi\Foundation\Models\Currency::factory()->createSafely(['code' => 'USD']);

    // Set lock date in future
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => LockDateType::HardLock,
        'locked_until' => now()->addDay(),
    ]);

    $so = SalesOrder::factory()->create([
        'company_id' => $this->company->id,
        'status' => SalesOrderStatus::Confirmed,
        'currency_id' => $currency->id,
    ]);

    $account = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);

    $dto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $so,
        invoice_date: now(), // Today is before lock date (tomorrow)
        due_date: now()->addDays(30),
        default_income_account_id: $account->id
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Kezi\Accounting\Exceptions\PeriodIsLockedException::class);
});
