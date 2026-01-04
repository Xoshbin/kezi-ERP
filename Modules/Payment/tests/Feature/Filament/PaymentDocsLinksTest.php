<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\CreatePayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages\EditPayment;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
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
    $invoice = Invoice::factory()
        ->for($this->company)
        ->withLines(1)
        ->create([
            'status' => InvoiceStatus::Posted,
        ]);

    $invoice->refresh();

    $this->get(InvoiceResource::getUrl('view', ['record' => $invoice]))
        ->assertSee('Register Payment');
});

it('shows Register Payment action on posted vendor bill with balance', function () {
    $bill = VendorBill::factory()
        ->for($this->company)
        ->withLines(1)
        ->create([
            'status' => VendorBillStatus::Posted,
        ]);

    $bill->refresh();

    $this->get(VendorBillResource::getUrl('view', ['record' => $bill]))
        ->assertSee('Register Payment');
});
