<?php

use App\Enums\Payments\PaymentStatus;
use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use App\Filament\Clusters\Accounting\Resources\Payments\Pages\ListPayments;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

it('shows docs action on payments list page header', function () {
    $this->get(PaymentResource::getUrl('index'))
        ->assertSee('Payments Guide');
});

it('shows docs action on create payment page', function () {
    livewire(CreatePayment::class)
        ->assertActionVisible('payments_docs');
});

it('shows docs action on edit payment page', function () {
    $payment = Payment::factory()->for($this->company)->create([
        'status' => PaymentStatus::Draft,
    ]);

    livewire(EditPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionVisible('payments_docs');
});

it('shows Register Payment action on posted invoice with balance', function () {
    $invoice = \App\Models\Invoice::factory()
        ->for($this->company)
        ->withLines(1)
        ->create([
            'status' => \App\Enums\Sales\InvoiceStatus::Posted,
        ]);

    $this->get(\App\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource::getUrl('edit', ['record' => $invoice]))
        ->assertSee('Register Payment');
});

it('shows Register Payment action on posted vendor bill with balance', function () {
    $bill = \App\Models\VendorBill::factory()
        ->for($this->company)
        ->withLines(1)
        ->create([
            'status' => \App\Enums\Purchases\VendorBillStatus::Posted,
        ]);

    $this->get(\App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource::getUrl('edit', ['record' => $bill]))
        ->assertSee('Register Payment');
});

