<?php

use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use function Pest\Livewire\livewire;
use Modules\Sales\Database\Factories\InvoiceFactory;



it('updates invoice status to posted in UI after confirmation', function () {
    // 1. Setup Data
    $company = \App\Models\Company::factory()->create();
    $invoice = Invoice::factory()
        ->withLines(1)
        ->create([
            'status' => InvoiceStatus::Draft,
            'company_id' => $company->id,
        ]);

    $user = \App\Models\User::factory()->create(['company_id' => $company->id]);
    $this->actingAs($user);
    \Filament\Facades\Filament::setTenant($company);

    // 2. Load Edit Page
    $component = livewire(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ]);

    // 2.5 Ensure form is filled (mount happens automatically in livewire helper, but good to know)


    // 3. Trigger Confirm Action
    $component->callAction('confirm');

    // 4. Verify Database State
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Posted);

    // 5. Verify Component State (This is where we expect the bug/issue)
    // We check if the component 'knows' the status is Posted.
    // If the component is dealing with a stale model, it might still think it's Draft.

    // We can check if the 'confirm' action is still visible (it should be hidden if Posted)
    $component->assertActionHidden('confirm');

    // Or check if 'register_payment' is visible (it should be visible if Posted)
    $component->assertActionVisible('register_payment');
});
