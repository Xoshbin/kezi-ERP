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

it('handles user without company', function () {
    $userWithoutCompany = User::factory()->create();
    // Don't attach any companies to this user
    $this->actingAs($userWithoutCompany);

    $component = Livewire::test(Dashboard::class);

    $component->assertOk();
    // Check for the English translation since tests run in English locale
    $component->assertSeeText('No Company');
});
