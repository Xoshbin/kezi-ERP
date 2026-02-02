<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages\ListAuditLogs;
use Kezi\Foundation\Models\AuditLog;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the audit log list page', function () {
    livewire(ListAuditLogs::class)
        ->assertSuccessful();
});

it('can list audit logs', function () {
    $logs = AuditLog::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    livewire(ListAuditLogs::class)
        ->assertCanSeeTableRecords($logs)
        ->assertCountTableRecords(3);
});

it('scopes audit logs to the active company', function () {
    $logInCompany = AuditLog::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $logInOtherCompany = AuditLog::factory()->create([
        'company_id' => $otherCompany->id,
        // user_id can be the same or different, scoping is by company
        'user_id' => \App\Models\User::factory()->create()->id,
    ]);

    livewire(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$logInCompany])
        ->assertCanNotSeeTableRecords([$logInOtherCompany]);
});
