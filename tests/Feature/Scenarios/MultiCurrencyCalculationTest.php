<?php

use Brick\Money\Money;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Inventory\Models\AdjustmentDocumentLine;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Models\Quote;
use Kezi\Sales\Models\QuoteLine;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $this->setUpWithConfiguredCompany();
    $this->setupInventoryTestEnvironment(); // Includes vendor, stockLocation

    // Ensure company uses IQD with 3 decimal places
    $this->baseCurrency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'decimal_places' => 3, 'symbol_position' => 'after']
    );
    $this->company->update(['currency_id' => $this->baseCurrency->id]);

    // Create a foreign currency (USD) with 2 decimal places
    $this->foreignCurrency = Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'symbol_position' => 'before']
    );

    $this->exchangeRate = 1250.0;
});

test('purchase order line correctly calculates company currency totals', function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $foreignCurrency = $this->foreignCurrency;
    $company = $this->company;
    $vendor = $this->vendor;
    $exchangeRate = $this->exchangeRate;

    $po = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $foreignCurrency->id,
        'exchange_rate_at_creation' => $exchangeRate,
    ]);

    $poLine = PurchaseOrderLine::factory()->for($po, 'purchaseOrder')->create([
        'quantity' => 10,
        // Unit price in USD
        'unit_price' => Money::of(100.25, $foreignCurrency->code),
        'unit_price_company_currency' => null,
        'subtotal_company_currency' => null,
    ]);

    // 100.25 USD * 1250 = 125,312.5 IQD
    // Since IQD has 3 decimals, the expected raw value is 125312.500
    // Total for 10 items: 10 * 125,312.5 = 1,253,125.000

    // First, let's test getUnitPriceInCompanyCurrency functionality
    /** @var Money $unitPriceCompany */
    $unitPriceCompany = $poLine->getUnitPriceInCompanyCurrency();

    expect($unitPriceCompany->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($unitPriceCompany->getAmount()->toFloat())->toBe(125312.5);

    /** @var Money $subtotalCompanyCurrency */
    $subtotalCompanyCurrency = $poLine->getSubtotalInCompanyCurrency();

    expect($subtotalCompanyCurrency->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($subtotalCompanyCurrency->getAmount()->toFloat())->toBe(1253125.0);
});

test('vendor bill correctly calculates company currency totals', function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $foreignCurrency = $this->foreignCurrency;
    $company = $this->company;
    $vendor = $this->vendor;
    $exchangeRate = $this->exchangeRate;

    /** @var \Kezi\Purchase\Models\VendorBill $vendorBill */
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $foreignCurrency->id,
        'exchange_rate_at_creation' => $exchangeRate,
        'total_amount_company_currency' => null,
        'total_tax_company_currency' => null,
    ]);

    // Add a line item of 10 units at $100.25 USD (Subtotal $1002.50 USD)
    VendorBillLine::factory()->for($vendorBill, 'vendorBill')->create([
        'quantity' => 10,
        'unit_price' => Money::of(100.25, $foreignCurrency->code),
        'subtotal' => Money::of(1002.50, $foreignCurrency->code),
        'unit_price_company_currency' => null,
        'subtotal_company_currency' => null,
        'total_line_tax_company_currency' => null,
        'total_line_tax' => Money::zero($foreignCurrency->code),
        'tax_id' => null,
        // Observer handles line math: unit_price * quantity => subtotal
    ]);

    // Adding tax just in case
    // The observer calls VendorBill::calculateTotalsFromLines which calculates total_amount and total_tax.
    // It also runs updateCompanyCurrencyTotals which applies the exchange rate.

    $vendorBill->refresh();

    // 1002.50 USD * 1250 = 1,253,125.000 IQD
    /** @var Money $totalAmountCompanyCurrency */
    $totalAmountCompanyCurrency = $vendorBill->total_amount_company_currency;

    expect($totalAmountCompanyCurrency)->not->toBeNull()
        ->and($totalAmountCompanyCurrency->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($totalAmountCompanyCurrency->getAmount()->toFloat())->toBe(1253125.0);
});

test('quote line correctly calculates company currency totals', function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $foreignCurrency = $this->foreignCurrency;
    $company = $this->company;
    $vendor = $this->vendor;
    $exchangeRate = $this->exchangeRate;

    /** @var \Kezi\Sales\Models\Quote $quote */
    $quote = Quote::factory()->create([
        'company_id' => $company->id,
        'partner_id' => $vendor->id, // Partner can be customer
        'currency_id' => $foreignCurrency->id,
        'exchange_rate' => $exchangeRate, // Note: quote uses exchange_rate property
    ]);

    $quoteLine = QuoteLine::factory()->for($quote, 'quote')->create([
        'quantity' => 5,
        'unit_price' => Money::of(10.50, $foreignCurrency->code), // $10.50 USD -> $52.50 total
        'discount_percentage' => 0,
        'unit_price_company_currency' => null,
        'subtotal_company_currency' => null,
        'discount_amount_company_currency' => null,
        'tax_amount_company_currency' => null,
        'total_company_currency' => null,
    ]);

    // $10.50 USD * 1250 = 13,125.000 IQD
    // line total $52.50 USD * 1250 = 65,625.000 IQD

    $quoteLine->refresh();

    /** @var Money $unitPriceCompanyCurrency */
    $unitPriceCompanyCurrency = $quoteLine->unit_price_company_currency;
    /** @var Money $totalCompanyCurrency */
    $totalCompanyCurrency = $quoteLine->total_company_currency;

    expect($unitPriceCompanyCurrency->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($unitPriceCompanyCurrency->getAmount()->toFloat())->toBe(13125.0)
        ->and($totalCompanyCurrency->getAmount()->toFloat())->toBe(65625.0);
});

test('invoice correctly calculates company currency totals', function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $foreignCurrency = $this->foreignCurrency;
    $company = $this->company;
    $vendor = $this->vendor;
    $exchangeRate = $this->exchangeRate;

    /** @var \Kezi\Sales\Models\Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $vendor->id,
        'currency_id' => $foreignCurrency->id,
        'exchange_rate_at_creation' => $exchangeRate,
        'total_amount_company_currency' => null,
        'total_tax_company_currency' => null,
    ]);

    InvoiceLine::factory()->for($invoice, 'invoice')->create([
        'quantity' => 20,
        'unit_price' => Money::of(5.25, $foreignCurrency->code), // $5.25 USD -> $105.00
        'subtotal_company_currency' => null,
        'total_line_tax_company_currency' => null,
    ]);

    $invoice->refresh();

    // 105.00 USD * 1250 = 131,250.000 IQD
    /** @var Money $totalAmountCompanyCurrency */
    $totalAmountCompanyCurrency = $invoice->total_amount_company_currency;

    expect($totalAmountCompanyCurrency)->not->toBeNull()
        ->and($totalAmountCompanyCurrency->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($totalAmountCompanyCurrency->getAmount()->toFloat())->toBe(131250.0);
});

test('adjustment document correctly calculates company currency totals', function () {
    /** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
    $foreignCurrency = $this->foreignCurrency;
    $company = $this->company;
    $exchangeRate = $this->exchangeRate;

    /** @var \Kezi\Inventory\Models\AdjustmentDocument $adjustmentDocument */
    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $foreignCurrency->id,
        'exchange_rate_at_creation' => $exchangeRate,
        'type' => \Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType::Miscellaneous,
        'subtotal_company_currency' => null,
        'total_amount_company_currency' => null,
        'total_tax_company_currency' => null,
    ]);

    AdjustmentDocumentLine::factory()->for($adjustmentDocument, 'adjustmentDocument')->create([
        'quantity' => 10,
        'unit_price' => Money::of(50.00, $foreignCurrency->code), // $500 total amount
        'total_line_tax' => Money::zero($foreignCurrency->code),
        'tax_id' => null,
    ]);

    $adjustmentDocument->refresh();

    // 500 USD * 1250 = 625000 IQD
    /** @var Money $totalAmountCompanyCurrency */
    $totalAmountCompanyCurrency = $adjustmentDocument->total_amount_company_currency;

    expect($totalAmountCompanyCurrency)->not->toBeNull()
        ->and($totalAmountCompanyCurrency->getCurrency()->getCurrencyCode())->toBe('IQD')
        ->and($totalAmountCompanyCurrency->getAmount()->toFloat())->toBe(625000.0);
});
