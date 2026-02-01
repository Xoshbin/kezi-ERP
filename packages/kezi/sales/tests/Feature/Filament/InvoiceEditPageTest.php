<?php

use App\Models\Company;
use App\Models\User;
use Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Livewire\Livewire;
use Tests\Traits\WithSuperAdminRole;

uses(WithSuperAdminRole::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company);

    setPermissionsTeamId($this->company->id);
    $this->assignSuperAdminRole($this->user, $this->company);

    $this->actingAs($this->user);

    // Set up Filament tenant context
    Filament::setTenant($this->company);

    $this->currency = Currency::factory()->createSafely(['code' => 'USD']);
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

test('view page shows pdf actions for posted invoice', function () {
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
        ->test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ViewInvoice::class, [
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
    $component->assertActionExists('post'); // For draft invoices
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
