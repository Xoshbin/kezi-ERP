<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Currency;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;

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
