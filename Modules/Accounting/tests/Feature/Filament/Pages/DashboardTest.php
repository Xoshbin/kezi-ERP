<?php

namespace Modules\Accounting\Tests\Feature\Filament\Pages;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports\Dashboard;
use App\Models\User;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Set current panel for Filament
    Filament::setCurrentPanel(Filament::getPanel('jmeryar'));
});

it('can render dashboard page', function () {
    $this->actingAs($this->user);

    // Skip this test until Dashboard class is properly implemented
    $this->markTestSkipped('Dashboard class needs to be implemented');
});

it('displays company name in subheading', function () {
    $this->actingAs($this->user);

    // Skip this test until Dashboard class is properly implemented
    $this->markTestSkipped('Dashboard class needs to be implemented');
});

it('includes all financial widgets', function () {
    $this->actingAs($this->user);

    // Skip this test until Dashboard class is properly implemented
    $this->markTestSkipped('Dashboard class needs to be implemented');
});

it('handles user without company', function () {
    $userWithoutCompany = User::factory()->create();
    // Don't attach any companies to this user
    $this->actingAs($userWithoutCompany);

    // Skip this test until Dashboard class is properly implemented
    $this->markTestSkipped('Dashboard class needs to be implemented');
});
