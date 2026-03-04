<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\QuoteStatus;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\CreateQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\EditQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\ListQuotes;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\ViewQuote;
use Kezi\Sales\Models\Quote;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('can list quotes', function () {
    // Ensure we use existing company and prevent factory from creating new currencies/companies
    // Partner::factory() creates a generic Partner. We must ensure it doesn't create a new Company/Currency.
    $partner = Partner::factory()->for($this->company)->create();

    $quotes = Quote::factory()->count(10)->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'partner_id' => $partner->id,
    ]);

    livewire(ListQuotes::class)
        ->assertCanSeeTableRecords($quotes);
});

it('can create a quote via filament form', function () {
    $partner = Partner::factory()->customer()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create(['unit_price' => Money::of(100, 'IQD')]);
    $currency = Currency::where('code', 'IQD')->first();

    livewire(CreateQuote::class)
        ->fillForm([
            'partner_id' => $partner->id,
            'currency_id' => $currency->id,
            'quote_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_percentage' => 0,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('quotes', [
        'company_id' => $this->company->id,
        'partner_id' => $partner->id,
        'status' => QuoteStatus::Draft->value,
    ]);

    $quote = Quote::latest()->first();
    assert($quote instanceof Quote);
    expect($quote->lines)->toHaveCount(1);

    expect($quote->total->getAmount()->toInt())->toBe(200);
});

it('can edit a draft quote', function () {
    $quote = Quote::factory()->draft()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
    ]);

    $product = Product::factory()->create(['company_id' => $quote->company_id]);

    // Add a line to satisfy validation
    $quote->lines()->create([
        'product_id' => $product->id,
        'quote_id' => $quote->id,
        'description' => 'Test',
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 100,
    ]);

    livewire(EditQuote::class, ['record' => $quote->getRouteKey()])
        ->fillForm([
            'notes' => 'Updated notes',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($quote->refresh()->notes)->toBe('Updated notes');
});

it('can view a quote and see actions', function () {
    $quote = Quote::factory()->draft()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
    ]);

    // Add lines so it can be sent
    $quote->lines()->create([
        'quote_id' => $quote->id,
        'description' => 'Test',
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 100,
    ]);

    livewire(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('send')
        ->assertActionHidden('accept')
        ->assertActionHidden('reject')
        ->callAction('send');

    expect($quote->refresh()->status)->toBe(QuoteStatus::Sent);
});

it('can accept a sent quote via view page', function () {
    $quote = Quote::factory()->sent()->for($this->company)->create();

    // dump($quote->lines->toArray());
    // Also dump product ID specifically
    // dump('Conversion Product ID:', $quote->lines->first()->product_id);

    livewire(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('accept')
        ->assertActionVisible('reject')
        ->callAction('accept');

    expect($quote->refresh()->status)->toBe(QuoteStatus::Accepted);
});

it('can create revision from sent quote', function () {
    $quote = Quote::factory()->sent()->for($this->company)->create(['version' => 1]);

    livewire(ViewQuote::class, ['record' => $quote->getRouteKey()])
        ->assertActionVisible('create_revision')
        ->callAction('create_revision');

    $this->assertDatabaseHas('quotes', [
        'previous_version_id' => $quote->id,
        'version' => 2,
        'status' => QuoteStatus::Draft->value,
    ]);

    expect($quote->refresh()->status)->toBe(QuoteStatus::Cancelled);
});
