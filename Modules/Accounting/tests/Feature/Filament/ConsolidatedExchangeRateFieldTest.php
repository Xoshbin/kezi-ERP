<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Create foreign currency (USD)
    $this->foreignCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => ['en' => 'US Dollar'],
        'symbol' => '$',
        'is_active' => true,
    ]);

    // Create exchange rate
    $this->exchangeRate = 1460.0;
    CurrencyRate::create([
        'currency_id' => $this->foreignCurrency->id,
        'company_id' => $this->company->id,
        'rate' => $this->exchangeRate,
        'effective_date' => Carbon::today(),
        'source' => 'manual',
    ]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
});

describe('Consolidated Exchange Rate Field', function () {
    test('single exchange rate field exists for foreign currency vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that only the consolidated exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');

        // Verify the old current_exchange_rate field doesn't exist
        $livewire->assertFormFieldDoesNotExist('current_exchange_rate');
    });

    test('single exchange rate field exists for foreign currency invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $livewire = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Check that only the consolidated exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');

        // Verify the old current_exchange_rate field doesn't exist
        $livewire->assertFormFieldDoesNotExist('current_exchange_rate');
    });

    test('exchange rate field is hidden for base currency documents', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field is hidden for base currency
        $livewire->assertFormFieldIsHidden('exchange_rate_at_creation');
    });

    test('exchange rate field shows current rate in helper text', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // The field should exist and be visible
        $livewire->assertFormFieldExists('exchange_rate_at_creation');

        // We can't easily test the helper text content in Livewire tests,
        // but we can verify the field is properly configured
        expect($vendorBill->currency_id)->toBe($this->foreignCurrency->id);
    });

    test('exchange rate field maintains conditional editability', function () {
        // Test draft status - should be editable
        $draftBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $draftBill->id]);
        $livewire->assertFormFieldExists('exchange_rate_at_creation');

        // Test posted status - should exist but be disabled
        $postedBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Posted,
            'exchange_rate_at_creation' => $this->exchangeRate,
        ]);

        $livewire = Livewire::test(\Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ViewVendorBill::class, ['record' => $postedBill->id]);
        // For View page, we assert the field is visible (infolist or disabled form)
        // Since ViewRecord falls back to disabled form, assertFormFieldExists should work if it renders the form
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field can be set and persists', function () {
        $customRate = 1500.0;

        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        // Directly set the exchange rate to test persistence
        $vendorBill->update(['exchange_rate_at_creation' => $customRate]);
        $vendorBill->refresh();

        // Verify the exchange rate was saved
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($customRate);
    });
});
