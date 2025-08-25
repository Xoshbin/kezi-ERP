<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Clusters\Accounting\Pages\Dashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Set current panel for Filament
    Filament::setCurrentPanel(Filament::getPanel('jmeryar'));
});

it('can render dashboard page', function () {
    $this->actingAs($this->user);

    $component = Livewire::test(Dashboard::class);

    $component->assertOk();
    $component->assertSeeText(__('dashboard.financial_dashboard'));
});

it('displays company name in subheading', function () {
    $this->actingAs($this->user);

    $component = Livewire::test(Dashboard::class);

    $component->assertOk();
    $component->assertSeeText($this->company->name);
});

it('includes all financial widgets', function () {
    $this->actingAs($this->user);

    $dashboard = new Dashboard();
    $widgets = $dashboard->getWidgets();

    expect($widgets)->toContain(\App\Filament\Clusters\Accounting\Widgets\FinancialStatsOverview::class);
    expect($widgets)->toContain(\App\Filament\Clusters\Accounting\Widgets\IncomeVsExpenseChart::class);
    expect($widgets)->toContain(\App\Filament\Clusters\Accounting\Widgets\CashFlowWidget::class);
});

it('handles user without company', function () {
    $userWithoutCompany = User::factory()->create();
    // Don't attach any companies to this user
    $this->actingAs($userWithoutCompany);

    // In tenancy setup, users without companies can't access the dashboard
    // The tenant context will be null, so widgets will return empty arrays
    $component = Livewire::test(Dashboard::class);

    $component->assertOk();
    // The dashboard should still render but with no data since no tenant is set
});
