<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Sales\Actions\Sales\UpdateInvoiceAction;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateInvoiceDTO;
use Jmeryar\Sales\DataTransferObjects\Sales\UpdateInvoiceLineDTO;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;
use Jmeryar\Sales\Models\InvoiceLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(UpdateInvoiceAction::class);
});

it('updates an invoice and its lines', function () {
    $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
        'currency_id' => $currency->id,
    ]);

    $invoiceLine = InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'company_id' => $this->company->id,
    ]);

    $product = \Jmeryar\Product\Models\Product::factory()->create(['company_id' => $this->company->id]);
    $incomeAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);

    $lineDto = new UpdateInvoiceLineDTO(
        description: 'Updated Product',
        quantity: 5,
        unit_price: Money::of(200, 'USD'),
        income_account_id: $incomeAccount->id,
        product_id: $product->id,
        tax_id: null
    );

    $dto = new UpdateInvoiceDTO(
        invoice: $invoice,
        customer_id: $invoice->customer_id,
        currency_id: $currency->id,
        invoice_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [$lineDto],
        fiscal_position_id: null
    );

    $updatedInvoice = $this->action->execute($dto);

    expect($updatedInvoice->invoiceLines)->toHaveCount(1);
    expect($updatedInvoice->invoiceLines->first()->description)->toBe('Updated Product');
    expect($updatedInvoice->total_amount->getAmount()->toFloat())->toBe(1000.0);

    // Ensure old line was deleted
    $this->assertDatabaseMissing('invoice_lines', ['id' => $invoiceLine->id]);
});

it('throws exception if invoice is not draft', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $dto = new UpdateInvoiceDTO(
        invoice: $invoice,
        customer_id: $invoice->customer_id,
        currency_id: $invoice->currency_id,
        invoice_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [],
        fiscal_position_id: null
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Jmeryar\Foundation\Exceptions\UpdateNotAllowedException::class, 'Cannot modify a non-draft invoice.');
});

it('respects lock date service', function () {
    $lockDate = now()->addDay();
    \Jmeryar\Accounting\Models\LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Jmeryar\Accounting\Enums\Accounting\LockDateType::HardLock,
        'locked_until' => $lockDate,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $dto = new UpdateInvoiceDTO(
        invoice: $invoice,
        customer_id: $invoice->customer_id,
        currency_id: $invoice->currency_id,
        invoice_date: now()->format('Y-m-d'),
        due_date: now()->addDays(30)->format('Y-m-d'),
        lines: [],
        fiscal_position_id: null
    );

    expect(fn () => $this->action->execute($dto))
        ->toThrow(\Jmeryar\Accounting\Exceptions\PeriodIsLockedException::class);
});
