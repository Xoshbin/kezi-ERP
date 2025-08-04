<?php

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceLine;

it('correctly resolves its currency_id from its parent invoice', function () {
    // Arrange
    $usd = Currency::factory()->create();
    $invoice = Invoice::factory()->create(['currency_id' => $usd->id]);
    $line = InvoiceLine::factory()->make(['invoice_id' => $invoice->id]);

    // Eager load the relationship just as the application would
    $line->load('invoice');

    // Act
    $currencyId = $line->currency_id; // This calls the getCurrencyIdAttribute() accessor

    // Assert
    expect($currencyId)->toBe($usd->id);
});
