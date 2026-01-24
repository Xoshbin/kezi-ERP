<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages\ListLockDates;
use Modules\Accounting\Models\LockDate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the lock date list page', function () {
    livewire(ListLockDates::class)
        ->assertSuccessful();
});

it('can list lock dates', function () {
    $lock1 = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Modules\Accounting\Enums\Accounting\LockDateType::TaxReturn,
    ]);
    $lock2 = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Modules\Accounting\Enums\Accounting\LockDateType::AllUsers,
    ]);

    livewire(ListLockDates::class)
        ->assertCanSeeTableRecords([$lock1, $lock2])
        ->assertCountTableRecords(2);
});

it('scopes lock dates to the active company', function () {
    $lockInCompany = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => \Modules\Accounting\Enums\Accounting\LockDateType::HardLock,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $lockInOtherCompany = LockDate::factory()->create([
        'company_id' => $otherCompany->id,
        'lock_type' => \Modules\Accounting\Enums\Accounting\LockDateType::HardLock,
    ]);

    livewire(ListLockDates::class)
        ->assertCanSeeTableRecords([$lockInCompany])
        ->assertCanNotSeeTableRecords([$lockInOtherCompany]);
});
