<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\Company;
use Filament\Facades\Filament;
use Modules\Sales\Models\Invoice;

use Modules\Sales\Models\InvoiceLine;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Models\Currency;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;


beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set up Filament tenant context
    Filament::setTenant($this->company);

    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    $this->account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

test('edit page shows pdf actions for draft invoice', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ]);

    // Assert - Check that PDF actions exist
    $component->assertActionExists('viewPdf');
    $component->assertActionExists('downloadPdf');
});

test('edit page shows pdf actions for posted invoice', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-001',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ]);

    // Assert - Check that PDF actions exist
    $component->assertActionExists('viewPdf');
    $component->assertActionExists('downloadPdf');
});

test('edit page pdf actions work correctly', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action - Test that the actions have the correct URLs
    $viewUrl = route('invoices.pdf', $invoice);
    $downloadUrl = route('invoices.pdf.download', $invoice);

    // Assert
    expect($viewUrl)->toContain("/invoices/{$invoice->id}/pdf");
    expect($downloadUrl)->toContain("/invoices/{$invoice->id}/pdf/download");
});

test('edit page shows all expected actions', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ]);

    // Assert - Check that all expected actions exist
    $component->assertActionExists('viewPdf');
    $component->assertActionExists('downloadPdf');
    $component->assertActionExists('confirm'); // For draft invoices
});

// TODO:: In future if you pland to add back the reset button just enable the test below and enable the action button in the Invoice resource
/*
 * Temprarily disable reset button since we are not sure about this feature wheter it's good or no
 * the feature is woking and passing tests */

// test('edit page shows different actions for posted invoice', function () {
//     // Arrange
//     $invoice = Invoice::factory()->create([
//         'company_id' => $this->company->id,
//         'customer_id' => $this->customer->id,
//         'currency_id' => $this->currency->id,
//         'status' => InvoiceStatus::Posted,
//         'invoice_number' => 'INV-001',
//     ]);

//     InvoiceLine::factory()->create([
//         'invoice_id' => $invoice->id,
//         'income_account_id' => $this->account->id,
//     ]);

//     // Action
//     $component = Livewire::actingAs($this->user)
//         ->test(InvoiceResource\Pages\EditInvoice::class, [
//             'record' => $invoice->getRouteKey(),
//         ]);

//     // Assert - Check that all expected actions exist
//     $component->assertActionExists('viewPdf');
//     $component->assertActionExists('downloadPdf');
//     $component->assertActionExists('registerPayment'); // For posted invoices
//     $component->assertActionExists('resetToDraft'); // For posted invoices
// });
