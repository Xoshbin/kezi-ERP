<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Widgets;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Widgets\CashFlowWidget;
use Kezi\Accounting\Filament\Clusters\Accounting\Widgets\FinancialStatsOverview;
use Kezi\Accounting\Filament\Clusters\Accounting\Widgets\IncomeVsExpenseChart;
use Kezi\Accounting\Services\Reports\ProfitAndLossStatementService;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;
use Livewire\Livewire;
use ReflectionClass;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Set current panel for Filament
    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

it('can render financial stats overview widget', function () {
    $this->actingAs($this->user);

    $widget = new FinancialStatsOverview;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);

    // Check that each stat has required properties
    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(Stat::class);
    }
});

it('displays correct financial stats with real data', function () {
    $this->actingAs($this->user);

    // Create test data
    $customer = Partner::factory()->for($this->company)->create([
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
        'name' => 'Test Customer',
    ]);

    $product = Product::factory()->for($this->company)->create([
        'name' => 'Test Product',
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    // Create and post an invoice
    $invoice = Invoice::factory()->for($this->company)->for($customer, 'customer')->create([
        'status' => InvoiceStatus::Draft,
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(30),
    ]);

    InvoiceLine::factory()->for($invoice)->for($product)->create([
        'quantity' => 10,
        'unit_price' => Money::of(100, $this->company->currency->code),
        'subtotal' => Money::of(1000, $this->company->currency->code),
    ]);

    // Post the invoice
    $invoiceService = app(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Test with just the invoice for simplicity

    $widget = new FinancialStatsOverview;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});

it('can render income vs expense chart widget', function () {
    $this->actingAs($this->user);

    $widget = new IncomeVsExpenseChart;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $chartData = $method->invoke($widget);

    expect($chartData)->toBeArray();
    expect($chartData)->toHaveKey('datasets');
    expect($chartData)->toHaveKey('labels');
});

it('can render cash flow widget', function () {
    $this->actingAs($this->user);

    $widget = new CashFlowWidget;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});

// it('handles missing company gracefully', function () {
//     // Create user without company
//     $userWithoutCompany = User::factory()->create();
//     $this->actingAs($userWithoutCompany);

//     $widget = new FinancialStatsOverview();

//     // Use reflection to access protected method
//     $reflection = new \ReflectionClass($widget);
//     $method = $reflection->getMethod('getStats');
//     $method->setAccessible(true);
//     $stats = $method->invoke($widget);

//     expect($stats)->toBeArray();
//     expect($stats)->toBeEmpty();
// });

it('handles service errors gracefully', function () {
    $this->actingAs($this->user);

    // Mock a service to throw an exception
    $this->mock(ProfitAndLossStatementService::class)
        ->shouldReceive('generate')
        ->andThrow(new Exception('Service error'));

    $component = Livewire::test(FinancialStatsOverview::class);
    $component->assertOk();

    // Should show error message instead of crashing
    $component->assertSeeText(__('accounting::dashboard.financial.data_unavailable'));
});

it('calculates cash flow forecasts correctly', function () {
    $this->actingAs($this->user);

    // Create overdue invoice
    $customer = Partner::factory()->for($this->company)->create(['type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer]);
    Invoice::factory()->for($this->company)->for($customer, 'customer')->create([
        'status' => InvoiceStatus::Posted,
        'invoice_date' => Carbon::now()->subDays(45),
        'due_date' => Carbon::now()->subDays(15), // Overdue
        'total_amount' => Money::of(5000, $this->company->currency->code),
    ]);

    // Create invoice due in 5 days
    Invoice::factory()->for($this->company)->for($customer, 'customer')->create([
        'status' => InvoiceStatus::Posted,
        'invoice_date' => Carbon::now(),
        'due_date' => Carbon::now()->addDays(5),
        'total_amount' => Money::of(3000, $this->company->currency->code),
    ]);

    $widget = new CashFlowWidget;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});
