<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use App\Services\InvoiceService;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Create foreign currency (USD) - use firstOrCreate to avoid conflicts in parallel tests
    $this->usd = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'USD Currency',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
        ]
    );

    // Create test data
    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \App\Enums\Products\ProductType::Service,
    ]);

    $this->expenseAccount = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->incomeAccount = Account::factory()->income()->create(['company_id' => $this->company->id]);
});

it('vendor bill posting uses fallback exchange rate when historical rate not available', function () {
    // Create a current exchange rate (not for the historical date)
    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'rate' => 1460.0,
        'effective_date' => Carbon::now(),
    ]);

    // Create vendor bill with historical date (no rate available for this date)
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->usd->id,
        'bill_date' => '2025-05-15', // Historical date
        'accounting_date' => '2025-05-15',
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(0, 'USD'),
    ]);

    VendorBillLine::factory()->create([
        'company_id' => $this->company->id,
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $this->product->id,
        'unit_price' => Money::of(100, 'USD'),
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Post the vendor bill - should use fallback rate
    $service = app(VendorBillService::class);
    $service->post($vendorBill, $this->user);

    $vendorBill->refresh();

    // Verify the bill was posted successfully
    expect($vendorBill->status->value)->toBe('posted');
    expect((float) $vendorBill->exchange_rate_at_creation)->toBe(1460.0);
    expect($vendorBill->total_amount_company_currency->__toString())->toContain('146000.000');

    // Verify journal entry was created
    expect($vendorBill->journal_entry_id)->not->toBeNull();
    expect($vendorBill->journalEntry)->not->toBeNull();
    expect($vendorBill->journalEntry->lines->count())->toBe(2);
});

it('invoice confirmation uses fallback exchange rate when historical rate not available', function () {
    // Create a current exchange rate (not for the historical date)
    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'rate' => 1460.0,
        'effective_date' => Carbon::now(),
    ]);

    // Create invoice with historical date (no rate available for this date)
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->usd->id,
        'invoice_date' => '2025-05-15', // Historical date
        'total_amount' => Money::of(200, 'USD'),
        'total_tax' => Money::of(0, 'USD'),
    ]);

    InvoiceLine::factory()->create([
        'company_id' => $this->company->id,
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'unit_price' => Money::of(200, 'USD'),
        'subtotal' => Money::of(200, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'income_account_id' => $this->incomeAccount->id,
    ]);

    // Confirm the invoice - should use fallback rate
    $service = app(InvoiceService::class);
    $service->confirm($invoice, $this->user);

    $invoice->refresh();

    // Verify the invoice was confirmed successfully
    expect($invoice->status->value)->toBe('posted');
    expect((float) $invoice->exchange_rate_at_creation)->toBe(1460.0);
    expect($invoice->total_amount_company_currency->__toString())->toContain('292000.000');

    // Verify journal entry was created
    expect($invoice->journal_entry_id)->not->toBeNull();
    expect($invoice->journalEntry)->not->toBeNull();
    expect($invoice->journalEntry->lines->count())->toBe(2);
});

it('journal entry creation handles missing exchange rates gracefully', function () {
    // Create a current exchange rate
    CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->usd->id,
        'rate' => 1460.0,
        'effective_date' => Carbon::now(),
    ]);

    // Create vendor bill with historical date
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'currency_id' => $this->usd->id,
        'bill_date' => '2025-05-15', // Historical date
        'accounting_date' => '2025-05-15',
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(0, 'USD'),
    ]);

    VendorBillLine::factory()->create([
        'company_id' => $this->company->id,
        'vendor_bill_id' => $vendorBill->id,
        'product_id' => $this->product->id,
        'unit_price' => Money::of(100, 'USD'),
        'subtotal' => Money::of(100, 'USD'),
        'total_line_tax' => Money::of(0, 'USD'),
        'expense_account_id' => $this->expenseAccount->id,
    ]);

    // Post the vendor bill - should not throw exception
    $service = app(VendorBillService::class);

    // This should not throw an exception
    $service->post($vendorBill, $this->user);

    $vendorBill->refresh();

    // Verify the bill was posted successfully with fallback rate
    expect($vendorBill->status->value)->toBe('posted');
    expect($vendorBill->exchange_rate_at_creation)->not->toBeNull();
    expect($vendorBill->journal_entry_id)->not->toBeNull();

    // Verify journal entry totals are balanced
    $journalEntry = $vendorBill->journalEntry;
    expect($journalEntry->total_debit->isEqualTo($journalEntry->total_credit))->toBeTrue();
});
