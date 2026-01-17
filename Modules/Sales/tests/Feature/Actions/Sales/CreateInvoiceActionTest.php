<?php

use App\Models\User;
use Brick\Money\Money;
use Modules\Accounting\Models\Account; // Updated path for LockDateService
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->partner = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    // The product definition was removed in the provided edit, keeping it for consistency if needed later, but following the edit.
    // $this->product = Product::factory()->create([
    //     'company_id' => $this->company->id,
    // ]);
    /** @var \Modules\Accounting\Models\Account $account */
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '1000', // Changed from 4000
        'name' => 'Sales', // Changed from Sales Account
        'currency_id' => $this->currency->id, // Added
    ]);
    $this->account = $account;

    // Account for locking service dep
    $this->mock(LockDateService::class, function ($mock) { // Updated LockDateService path
        $mock->shouldReceive('enforce')->andReturnTrue();
    });
});

test('it can create a draft invoice with lines', function () {
    /** @var \Tests\TestCase $this */
    /** @var \Modules\Product\Models\Product $product */
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $dto = new CreateInvoiceDTO(
        company_id: $this->company->id,
        customer_id: $this->partner->id,
        currency_id: $this->currency->id,
        invoice_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [
            new CreateInvoiceLineDTO(
                description: 'Service',
                quantity: 1,
                unit_price: Money::of(100, 'USD'),
                income_account_id: $this->account->id,
                product_id: $product->id,
                tax_id: null
            ),
        ],
        fiscal_position_id: null,
    );

    // Mock LockDateService
    $this->mock(LockDateService::class, function ($mock) {
        $mock->shouldReceive('enforce')->andReturnNull();
    });

    $action = app(CreateInvoiceAction::class);
    $invoice = $action->execute($dto);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->invoiceLines)->toHaveCount(1);

    $line = $invoice->invoiceLines->first();
    $this->assertNotNull($line);
    expect($line->unit_price->getAmount()->toFloat())->toBe(100.0); // Changed from ->lines
});
