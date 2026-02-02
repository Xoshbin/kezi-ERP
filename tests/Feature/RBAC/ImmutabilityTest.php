<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset permissions
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Seed Permissions
    // Resetting database via RefreshDatabase handles table clearing,
    // but we need the seed to populate permissions table again for each test
    $this->seed(\Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('journal entry immutability: cannot delete posted journal entry even with permission', function () {
    // 1. Setup User and Role
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    setPermissionsTeamId($company->id);

    $role = Role::create(['name' => 'accountant_test', 'company_id' => $company->id]);
    $role->givePermissionTo(['delete_journal_entry']);

    $user->assignRole($role);

    // 2. Create Posted Journal Entry
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'state' => JournalEntryState::Posted,
        'is_posted' => true,
    ]);

    // 3. Act & Assert
    expect($user->can('delete', $journalEntry))->toBeFalse('Should not be able to delete posted journal entry');
});

test('journal entry RBAC: user without permission cannot delete draft journal entry', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    setPermissionsTeamId($company->id);

    // User has NO permissions

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'state' => JournalEntryState::Draft,
        'is_posted' => false,
    ]);

    expect($user->can('delete', $journalEntry))->toBeFalse('User without permission should not delete draft entry');
});

test('journal entry RBAC: user with permission can delete draft journal entry', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    setPermissionsTeamId($company->id);

    $role = Role::create(['name' => 'accountant_test_2', 'company_id' => $company->id]);
    $role->givePermissionTo(['delete_journal_entry']);
    $user->assignRole($role);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'state' => JournalEntryState::Draft,
        'is_posted' => false,
    ]);

    expect($user->can('delete', $journalEntry))->toBeTrue('User with permission should delete draft entry');
});

test('invoice immutability: cannot delete posted invoice', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    setPermissionsTeamId($company->id);

    $role = Role::create(['name' => 'sales_manager_test', 'company_id' => $company->id]);
    $role->givePermissionTo(['delete_invoice']);
    $user->assignRole($role);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'status' => InvoiceStatus::Posted,
    ]);

    expect($user->can('delete', $invoice))->toBeFalse();
});

test('vendor bill immutability: cannot delete posted vendor bill', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    setPermissionsTeamId($company->id);

    $role = Role::create(['name' => 'purchase_manager_test', 'company_id' => $company->id]);
    $role->givePermissionTo(['delete_vendor_bill']);
    $user->assignRole($role);

    $bill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'status' => VendorBillStatus::Posted,
    ]);

    expect($user->can('delete', $bill))->toBeFalse();
});
