<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Pos\Enums\PosSessionStatus;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions\PosSessionResource;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

/**
 * @property \App\Models\Company $company
 *
 * @method void setupWithConfiguredCompany()
 */
uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can calculate navigation badge count', function () {
    PosSession::factory()->create([
        'company_id' => $this->company->id,
        'status' => PosSessionStatus::Opened,
    ]);

    $badge = PosSessionResource::getNavigationBadge();

    expect($badge)->toBe('1');
});
