<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Pos\Filament\Clusters\Pos\Pages\PosDashboard;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosOrders\PosOrderResource;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions\PosSessionResource;
use Kezi\Pos\Filament\Clusters\Pos\Widgets\PosSalesTrendChart;
use Kezi\Pos\Filament\Clusters\Pos\Widgets\PosStatsOverviewWidget;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

it('can render pos dashboard', function () {
    $this->actingAs($this->user);

    $this->get(PosDashboard::getUrl())
        ->assertSuccessful();
});

it('can view pos session resource index', function () {
    $this->actingAs($this->user);

    $this->get(PosSessionResource::getUrl('index'))
        ->assertSuccessful();
});

it('can view pos order resource index', function () {
    $this->actingAs($this->user);

    $this->get(PosOrderResource::getUrl('index'))
        ->assertSuccessful();
});

it('shows correct stats in stats overview widget', function () {
    $this->actingAs($this->user);

    $profile = \Kezi\Pos\Models\PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'company_id' => $this->company->id,
        'status' => 'opened',
    ]);

    // Create orders with 100.00 amount each
    PosOrder::factory()->count(3)->create([
        'pos_session_id' => $session->id,
        'ordered_at' => now(),
        'total_amount' => 100, // 100.00
        'status' => 'paid',
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    livewire(PosStatsOverviewWidget::class)
        ->assertSee('Total Sales (Today)')
        ->assertSee('300.00') // 3 * 100.00
        ->assertSee('3')
        ->assertSee('Active Sessions')
        ->assertSee('1');
});

it('shows correct data in sales trend chart', function () {
    $this->actingAs($this->user);

    $profile = \Kezi\Pos\Models\PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'company_id' => $this->company->id,
    ]);

    // Create order at 10:00 with 50.00 amount
    PosOrder::factory()->create([
        'pos_session_id' => $session->id,
        'ordered_at' => now()->startOfDay()->setHour(10), // Ensure it is today 10:00
        'total_amount' => 50, // 50.00
        'status' => 'paid',
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    // Use reflection to access protected getData method
    $widget = new PosSalesTrendChart;
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);

    $data = $method->invoke($widget);

    // Expect 50.00 at hour 10
    expect($data['datasets'][0]['data'][10])->toBe(50.0);
    // Expect 0 at other hours
    expect($data['datasets'][0]['data'][9])->toBe(0);
});
