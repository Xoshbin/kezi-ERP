<?php

use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    // Set up Filament tenant context
    \Filament\Facades\Filament::setTenant($this->company);

    $this->currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $this->customer = \Modules\Foundation\Models\Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    $this->account = \Modules\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

test('edit page shows pdf actions for draft invoice', function () {
    // Arrange
    $invoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ]);

    // Assert - Check that PDF actions exist
    $component->assertActionExists('viewPdf');
    $component->assertActionExists('downloadPdf');
});

test('edit page shows pdf actions for posted invoice', function () {
    // Arrange
    $invoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-001',
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ]);

    // Assert - Check that PDF actions exist
    $component->assertActionExists('viewPdf');
    $component->assertActionExists('downloadPdf');
});

test('edit page pdf actions work correctly', function () {
    // Arrange
    $invoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
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
    $invoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $component = Livewire::actingAs($this->user)
        ->test(\App\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice::class, [
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
