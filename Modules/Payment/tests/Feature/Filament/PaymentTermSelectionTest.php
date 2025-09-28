<?php

use Modules\Sales\Models\Invoice;
use function Pest\Livewire\livewire;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Foundation\Models\PaymentTerm;

use Modules\Foundation\Models\PaymentTermLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Enums\PaymentTerms\PaymentTermType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ListVendorBills;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Create a payment term
    $this->paymentTerm = PaymentTerm::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Net 30',
        'description' => '30 days payment term',
    ]);

    PaymentTermLine::factory()->create([
        'payment_term_id' => $this->paymentTerm->id,
        'sequence' => 1,
        'type' => \Modules\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
        'days' => 30,
        'percentage' => 100.0,
    ]);
});

it('can select payment term in invoice form', function () {
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $this->company->currency_id,
            'journal_id' => $journal->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'payment_term_id' => $this->paymentTerm->id,
        ])
        ->assertFormFieldExists('payment_term_id')
        ->assertHasNoFormErrors();
});

it('can select payment term in vendor bill form', function () {
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'journal_id' => $journal->id,
            'bill_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'payment_term_id' => $this->paymentTerm->id,
        ])
        ->assertFormFieldExists('payment_term_id')
        ->assertHasNoFormErrors();
});

it('displays payment term in invoice table', function () {
    $customer = Partner::factory()->customer()->create([
        'company_id' => $this->company->id,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'payment_term_id' => $this->paymentTerm->id,
    ]);

    livewire(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertTableColumnExists('paymentTerm.name');
});

it('displays payment term in vendor bill table', function () {
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $vendor->id,
        'currency_id' => $this->company->currency_id,
        'payment_term_id' => $this->paymentTerm->id,
    ]);

    livewire(ListVendorBills::class)
        ->assertCanSeeTableRecords([$vendorBill])
        ->assertTableColumnExists('paymentTerm.name');
});
