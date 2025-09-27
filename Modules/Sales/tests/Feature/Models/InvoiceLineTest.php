<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('correctly resolves currency through its parent invoice relationship', function () {
    // Arrange
    $usd = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $invoice = \Modules\Sales\Models\Invoice::factory()->create(['currency_id' => $usd->id]);
    $line = \Modules\Sales\Models\InvoiceLine::factory()->create(['invoice_id' => $invoice->id]);

    // Eager load the relationship just as the application would
    $line->load('invoice.currency');

    // Act - Access the currency through the relationship
    $currency = $line->invoice->currency;

    // Assert
    expect($currency->id)->toBe($usd->id);
    expect($currency->code)->toBe('USD');
});
