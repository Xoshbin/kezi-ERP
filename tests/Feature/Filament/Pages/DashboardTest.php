<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\Dashboard;
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

    expect($widgets)->toContain(\App\Filament\Widgets\FinancialStatsOverview::class);
    expect($widgets)->toContain(\App\Filament\Widgets\IncomeVsExpenseChart::class);
    expect($widgets)->toContain(\App\Filament\Widgets\CashFlowWidget::class);
});

it('denies access to user without company', function () {
    $userWithoutCompany = User::factory()->create();
    // Don't attach any companies to this user
    $this->actingAs($userWithoutCompany);

    // With Filament tenancy, a user without companies should not be able to access tenant-scoped pages
    // Filament should handle this automatically through its tenancy system

    // Try to access the dashboard directly via HTTP request (more realistic test)
    $response = $this->get('/jmeryar');

    // User should be redirected or get an error, not see the dashboard
    // Filament typically redirects to tenant selection or registration page
    expect($response->status())->not->toBe(200);
});
