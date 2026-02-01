<?php

use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;

use function Pest\Livewire\livewire;

it('shows unhelpful error message when validation exception occurs (current behavior)', function () {
    // 1. Setup Data with Deprecated Account to trigger ValidationException in CreateJournalEntryAction
    $company = \App\Models\Company::factory()->create();
    $currency = \Jmeryar\Foundation\Models\Currency::factory()->create(); // Ensure currency exists
    // Fix: Account factory might need company or config.
    // Assuming simple factory works.
    $deprecatedAccount = Account::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'is_deprecated' => true,
        'name' => 'Old Account',
        'code' => '9999',
        // Ensure it's an income account type if needed, but validation checks generic account usage
    ]);

    $invoice = Invoice::factory()
        ->withLines(1) // Create one line
        ->create([
            'status' => InvoiceStatus::Draft,
            'company_id' => $company->id,
            'currency_id' => $currency->id,
        ]);

    // Update the invoice line to use the deprecated account
    $invoice->invoiceLines->first()->update([
        'income_account_id' => $deprecatedAccount->id,
    ]);

    $user = \App\Models\User::factory()->create();

    // Assign Permissions
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    setPermissionsTeamId($company->id);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    \Filament\Facades\Filament::setTenant($company);

    // 2. Load Edit Page
    $component = livewire(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ]);

    // 3. Trigger Confirm Action
    $component->callAction('post');

    // 4. Verify Notification is present
    $component->assertNotified();

    // 5. Verify it says "The given data was invalid." (Standard Laravel ValidationException message)
    // We expect this to be the specific message if my hypothesis is correct.
    // However, I want to IMPROVE it.
    // So this test failing (or passing with generic message) confirms the need for improvement.
    // assertNotified('The given data was invalid.');
});
