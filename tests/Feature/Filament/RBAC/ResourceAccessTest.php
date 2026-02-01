<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\EditJournalEntry;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use Kezi\Accounting\Models\JournalEntry;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(\Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->company = \App\Models\Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    setPermissionsTeamId($this->company->id);

    $this->actingAs($this->user);
    Filament::setTenant($this->company);
});

test('unauthorized user cannot access journal entries list', function () {
    livewire(ListJournalEntries::class)
        ->assertForbidden();
});

test('authorized user can access journal entries list', function () {
    $role = Role::create(['name' => 'test_viewer', 'company_id' => $this->company->id]);
    $role->givePermissionTo('view_any_journal_entry');
    $this->user->assignRole($role);

    livewire(ListJournalEntries::class)
        ->assertSuccessful();
});

test('user without create permission cannot see create page', function () {
    livewire(CreateJournalEntry::class)
        ->assertForbidden();
});

test('user with create permission can see create page', function () {
    $role = Role::create(['name' => 'test_creator', 'company_id' => $this->company->id]);
    $role->givePermissionTo(['create_journal_entry', 'view_any_journal_entry']);
    $this->user->assignRole($role);

    livewire(CreateJournalEntry::class)
        ->assertSuccessful();
});

test('user cannot edit posted journal entry', function () {
    $role = Role::create(['name' => 'test_editor', 'company_id' => $this->company->id]);
    // Permissions needed: update to edit, but also view to see the record
    $role->givePermissionTo(['update_journal_entry', 'view_journal_entry', 'view_any_journal_entry']);
    $this->user->assignRole($role);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'state' => JournalEntryState::Posted,
        'is_posted' => true,
    ]);

    livewire(EditJournalEntry::class, ['record' => $journalEntry->getRouteKey()])
        ->assertForbidden(); // Should be restricted by Policy (update -> false if posted)
});

test('user can edit draft journal entry with permission', function () {
    $role = Role::create(['name' => 'test_editor_2', 'company_id' => $this->company->id]);
    $role->givePermissionTo(['update_journal_entry', 'view_journal_entry', 'view_any_journal_entry']);
    $this->user->assignRole($role);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'state' => JournalEntryState::Draft,
        'is_posted' => false,
    ]);

    livewire(EditJournalEntry::class, ['record' => $journalEntry->getRouteKey()])
        ->assertSuccessful();
});
