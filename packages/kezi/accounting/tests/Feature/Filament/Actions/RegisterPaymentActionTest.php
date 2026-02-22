<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Actions;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ViewVendorBill;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Models\Partner;
use Kezi\Payment\Models\Payment;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
    Filament::setTenant($this->company);

    $this->journal = Journal::factory()->for($this->company)->create(['type' => 'bank']);
    $this->currency = $this->company->currency;

    // Accounts for bill
    $this->payableAccount = Account::factory()->for($this->company)->create(['type' => 'payable']);
    $this->expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
});

describe('Vendor Bill Register Payment Action', function () {
    it('can register payment for a vendor bill', function () {
        $vendor = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor]);
        $bill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->currency->id,
            'status' => VendorBillStatus::Posted,
            'total_amount' => Money::of(500, $this->currency->code),
        ]);

        livewire(ViewVendorBill::class, [
            'record' => $bill->id,
        ])
            ->callAction('register_payment', data: [
                'journal_id' => $this->journal->id,
                'payment_date' => now()->toDateString(),
                'amount' => 500,
                'reference' => 'Bill Payment 001',
                'currency_id' => $this->currency->id,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertDatabaseHas(Payment::class, [
            'company_id' => $this->company->id,
            'journal_id' => $this->journal->id,
            'amount' => 500000, // Correcting based on test output
            'payment_type' => \Kezi\Payment\Enums\Payments\PaymentType::Outbound->value,
            'reference' => 'Bill Payment 001',
        ]);

        expect($bill->fresh()->getRemainingAmount()->isZero())->toBeTrue();
    });
});

describe('Invoice Register Payment Action', function () {
    it('can register payment for an invoice', function () {
        $customer = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer]);
        $invoice = \Kezi\Sales\Models\Invoice::factory()->for($this->company)->create([
            'customer_id' => $customer->id,
            'currency_id' => $this->currency->id,
            'status' => \Kezi\Sales\Enums\Sales\InvoiceStatus::Posted,
            'total_amount' => Money::of(1000, $this->currency->code),
        ]);

        livewire(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('register_payment', data: [
                'journal_id' => $this->journal->id,
                'payment_date' => now()->toDateString(),
                'amount' => 1000,
                'reference' => 'Invoice Payment 001',
                'currency_id' => $this->currency->id,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertDatabaseHas(Payment::class, [
            'company_id' => $this->company->id,
            'journal_id' => $this->journal->id,
            'amount' => 1000000, // Correcting based on test output
            'payment_type' => \Kezi\Payment\Enums\Payments\PaymentType::Inbound->value,
            'reference' => 'Invoice Payment 001',
        ]);

        expect($invoice->fresh()->getRemainingAmount()->isZero())->toBeTrue();
    });

    it('can register payment for an invoice in a different currency', function () {
        // 1. Setup secondary currency (USD) if it doesn't exist
        $usd = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
            ]
        );

        // 2. Setup exchange rate: 1 USD = 1,500 IQD.
        // Since IQD is base (from setupWithConfiguredCompany), and USD is foreign.
        // 1 USD = 1,500 Base units.
        CurrencyRate::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $usd->id,
            'rate' => 1500.0,
            'effective_date' => now()->subDay(),
        ]);

        $customer = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer]);
        $invoice = \Kezi\Sales\Models\Invoice::factory()->for($this->company)->create([
            'customer_id' => $customer->id,
            'currency_id' => $usd->id, // USD
            'status' => \Kezi\Sales\Enums\Sales\InvoiceStatus::Posted,
            'total_amount' => Money::of(100, $usd->code),
        ]);

        // Invoice is 100 USD. We pay in IQD (base).
        // 100 * 1500 = 150,000 IQD.
        $iqdAmount = 150000;

        livewire(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('register_payment', data: [
                'journal_id' => $this->journal->id,
                'payment_date' => now()->toDateString(),
                'currency_id' => $this->currency->id, // IQD
                'amount' => $iqdAmount,
                'reference' => 'Multi-currency Payment',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        // IQD in CompanyBuilder has 3 decimal places.
        // 150,000.000 -> 150,000,000
        $this->assertDatabaseHas(Payment::class, [
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'amount' => 150000000,
            'reference' => 'Multi-currency Payment',
        ]);

        expect($invoice->fresh()->getRemainingAmount()->isZero())->toBeTrue();
    });
});
