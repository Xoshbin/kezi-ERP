<?php

namespace Modules\Accounting\Tests\Feature\Filament\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Filament\Clusters\Accounting\Pages\Dashboard;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Set current panel for Filament
    Filament::setCurrentPanel(Filament::getPanel('jmeryar'));
});

it('can render dashboard page', function () {
    $this->actingAs($this->user);

    $this->get(Dashboard::getUrl())
        ->assertSuccessful()
        ->assertSee(__('accounting::dashboard.financial_dashboard'));
});

it('displays company name in subheading', function () {
    $this->actingAs($this->user);

    $this->get(Dashboard::getUrl())
        ->assertSuccessful()
        ->assertSee($this->company->name);
});

it('includes all financial widgets', function () {
    $this->actingAs($this->user);

    $this->get(Dashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeLivewire(\Modules\Accounting\Filament\Clusters\Accounting\Widgets\FinancialStatsOverview::class)
        ->assertSeeLivewire(\Modules\Accounting\Filament\Clusters\Accounting\Widgets\IncomeVsExpenseChart::class)
        ->assertSeeLivewire(\Modules\Accounting\Filament\Clusters\Accounting\Widgets\CashFlowWidget::class);
});

it('handles user without company', function () {
    $userWithoutCompany = User::factory()->create();
    // Don't attach any companies to this user
    $this->actingAs($userWithoutCompany);

    // Expect 404 because user has no company and tenancy is required
    $this->get(Dashboard::getUrl(['tenant' => 'undefined']))
        ->assertStatus(404);
});
