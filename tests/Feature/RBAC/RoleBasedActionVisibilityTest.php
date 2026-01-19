<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ListInvoices;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset permission cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Seed roles and permissions
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Create company
    $this->company = \App\Models\Company::factory()->create();

    // Create user and attach to company
    $this->user = User::factory()->create(['email' => 'testuser@test.com']);
    $this->user->companies()->attach($this->company);

    // Set team context
    setPermissionsTeamId($this->company->id);

    // Create roles
    $this->viewerRole = Role::firstOrCreate(['name' => 'viewer', 'company_id' => $this->company->id]);
    $this->editorRole = Role::firstOrCreate(['name' => 'editor', 'company_id' => $this->company->id]);

    // Ensure permissions exist
    Permission::firstOrCreate(['name' => 'view_any_invoice', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view_invoice', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create_invoice', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'update_invoice', 'guard_name' => 'web']);

    // Assign view permissions to viewer
    $this->viewerRole->givePermissionTo(['view_any_invoice', 'view_invoice']);

    // Assign create/edit permissions to editor
    $this->editorRole->givePermissionTo(['view_any_invoice', 'view_invoice', 'create_invoice', 'update_invoice']);

    // Authenticate the user
    $this->actingAs($this->user);

    // Set Filament context
    Filament::setTenant($this->company);
    Filament::setCurrentPanel(Filament::getPanel('jmeryar'));
});

test('viewer cannot see create invoice action', function () {
    $this->user->assignRole($this->viewerRole);

    livewire(ListInvoices::class)
        ->assertSuccessful()
        ->assertActionHidden('create');
});

test('editor can see create invoice action', function () {
    $this->user->assignRole($this->editorRole);

    livewire(ListInvoices::class)
        ->assertSuccessful()
        ->assertActionVisible('create');
});

test('viewer cannot see edit action on draft invoice', function () {
    $this->user->assignRole($this->viewerRole);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    livewire(ListInvoices::class)
        ->assertTableActionHidden('edit', $invoice);
});

test('editor can see edit action on draft invoice', function () {
    $this->user->assignRole($this->editorRole);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    livewire(ListInvoices::class)
        ->assertTableActionVisible('edit', $invoice);
});
