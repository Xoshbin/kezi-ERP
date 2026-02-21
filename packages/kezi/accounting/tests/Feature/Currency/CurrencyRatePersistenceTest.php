<?php

namespace Kezi\Accounting\Tests\Feature\Currency;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Ensure we have a foreign currency (USD)
    $this->usdCurrency = Currency::factory()->createSafely([
        'code' => 'USD',
        'name' => ['en' => 'US Dollar'],
        'symbol' => '$',
    ]);

    // Create a customer
    $this->customer = \Kezi\Foundation\Models\Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Create accounts
    $this->receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'receivable',
    ]);
    $this->incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
    ]);

    // Create a journal
    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'sale',
    ]);
});

test('it creates a central currency rate record when creating an invoice with a custom rate', function () {
    // 1. Ensure NO currency rates exist for USD
    expect(CurrencyRate::where('currency_id', $this->usdCurrency->id)->count())->toBe(0);

    // 2. Create an invoice in USD with a custom exchange rate
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->usdCurrency->id,
        'exchange_rate_at_creation' => 1500.0,
        'status' => \Kezi\Sales\Enums\Sales\InvoiceStatus::Draft,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'unit_price' => Money::of(100, 'USD'),
        'income_account_id' => $this->incomeAccount->id,
    ]);

    // 3. Confirm the invoice (which triggers processMultiCurrencyAmounts)
    app(InvoiceService::class)->confirm($invoice, $this->user);

    // 4. Verify the invoice has the correct rate
    expect((float) $invoice->fresh()->exchange_rate_at_creation)->toEqual(1500.0);

    // 5. Verify a CurrencyRate record WAS created in the central table
    $rate = CurrencyRate::where('currency_id', $this->usdCurrency->id)
        ->where('company_id', $this->company->id)
        ->first();

    expect($rate)->not->toBeNull()
        ->and((float) $rate->rate)->toEqual(1500.0)
        ->and($rate->source)->toBe('transaction');
});

test('it can create an invoice in the default currency without any available currency rate records', function () {
    // 1. Ensure NO currency rates exist for the base currency
    expect(CurrencyRate::where('currency_id', $this->company->currency_id)->count())->toBe(0);

    // 2. Create an invoice in the base currency
    $baseCurrency = $this->company->currency;

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $baseCurrency->id,
        'status' => \Kezi\Sales\Enums\Sales\InvoiceStatus::Draft,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'unit_price' => Money::of(100000, $baseCurrency->code),
        'income_account_id' => $this->incomeAccount->id,
    ]);

    // 3. Confirm the invoice
    // This should NOT throw "No exchange rate found" exception
    app(InvoiceService::class)->confirm($invoice, $this->user);

    // 4. Verify the invoice is confirmed successfully
    expect($invoice->fresh()->status)->toBe(\Kezi\Sales\Enums\Sales\InvoiceStatus::Posted);
    expect((float) $invoice->fresh()->exchange_rate_at_creation)->toEqual(1.0);
});
