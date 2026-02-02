<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Kezi\Sales\Actions\Sales\CreateInvoiceLineAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateInvoiceLineAction::class);
});

it('creates an invoice line with subtotal and tax calculation', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);
    $product = \Kezi\Product\Models\Product::factory()->create(['company_id' => $this->company->id]);
    $incomeAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);
    $tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 0.1, // 10%
    ]);

    $dto = new CreateInvoiceLineDTO(
        product_id: $product->id,
        description: 'Test Invoice Line',
        quantity: 10,
        unit_price: Money::of(100, $invoice->currency->code),
        tax_id: $tax->id,
        income_account_id: $incomeAccount->id
    );

    $invoiceLine = $this->action->execute($invoice, $dto);

    expect($invoiceLine->subtotal->getAmount()->toFloat())->toBe(1000.0);
    expect($invoiceLine->total_line_tax->getAmount()->toFloat())->toBe(100.0);
    expect($invoiceLine->description)->toBe('Test Invoice Line');
    expect($invoiceLine->invoice_id)->toBe($invoice->id);
});

it('creates an invoice line without tax', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);
    $product = \Kezi\Product\Models\Product::factory()->create(['company_id' => $this->company->id]);
    $incomeAccount = Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income']);

    $dto = new CreateInvoiceLineDTO(
        product_id: $product->id,
        description: 'Test Invoice Line No Tax',
        quantity: 2,
        unit_price: Money::of(50, $invoice->currency->code),
        tax_id: null,
        income_account_id: $incomeAccount->id
    );

    $invoiceLine = $this->action->execute($invoice, $dto);

    expect($invoiceLine->subtotal->getAmount()->toFloat())->toBe(100.0);
    expect($invoiceLine->total_line_tax->getAmount()->toFloat())->toBe(0.0);
});
