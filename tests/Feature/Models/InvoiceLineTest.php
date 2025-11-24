<?php

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Tax;
use App\Models\Account;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('correctly resolves currency through its parent invoice relationship', function () {
    // Arrange
    $usd = Currency::factory()->create(['code' => 'USD']);
    $invoice = Invoice::factory()->create(['currency_id' => $usd->id]);
    $line = InvoiceLine::factory()->create(['invoice_id' => $invoice->id]);

    // Eager load the relationship just as the application would
    $line->load('invoice.currency');

    // Act - Access the currency through the relationship
    $currency = $line->invoice->currency;

    // Assert
    expect($currency->id)->toBe($usd->id);
    expect($currency->code)->toBe('USD');
});

it('automatically calculates subtotal and tax amount on save', function () {
    $invoice = Invoice::factory()->create();

    $tax = Tax::factory()->create([
        'company_id' => $invoice->company_id,
        'rate' => 0.15, // 15%
    ]);

    $incomeAccount = Account::factory()->create([
         'company_id' => $invoice->company_id,
         'type' => \App\Enums\Accounting\AccountType::Income
    ]);

    $line = new InvoiceLine();
    $line->invoice_id = $invoice->id;
    $line->company_id = $invoice->company_id;
    $line->income_account_id = $incomeAccount->id;
    $line->description = 'Test Item';
    $line->quantity = 2;
    $line->unit_price = Money::of(100, $invoice->currency->code);
    $line->tax_id = $tax->id;

    $line->save();

    $freshLine = InvoiceLine::find($line->id);

    // Subtotal: 2 * 100 = 200
    expect($freshLine->subtotal->getAmount()->toFloat())->toBe(200.0);

    // Tax: 200 * 0.15 = 30
    expect($freshLine->total_line_tax->getAmount()->toFloat())->toBe(30.0);
});
