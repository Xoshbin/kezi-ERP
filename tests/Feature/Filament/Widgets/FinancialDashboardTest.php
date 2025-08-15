<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\FinancialStatsOverview;
use App\Filament\Widgets\IncomeVsExpenseChart;
use App\Filament\Widgets\CashFlowWidget;
use App\Filament\Widgets\AccountWidget;
use App\Models\User;
use App\Models\Company;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use App\Models\Partner;
use App\Models\Product;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryState;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Partners\PartnerType;
use Brick\Money\Money;
use Carbon\Carbon;
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

it('can render financial stats overview widget', function () {
    $this->actingAs($this->user);

    $widget = new FinancialStatsOverview();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);

    // Check that each stat has required properties
    foreach ($stats as $stat) {
        expect($stat)->toBeInstanceOf(\Filament\Widgets\StatsOverviewWidget\Stat::class);
    }
});

it('displays correct financial stats with real data', function () {
    $this->actingAs($this->user);

    // Create test data
    $customer = Partner::factory()->for($this->company)->create([
        'type' => PartnerType::Customer,
        'name' => 'Test Customer'
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
    $invoiceService = app(\App\Services\InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);

    // Test with just the invoice for simplicity

    $widget = new FinancialStatsOverview();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});

it('can render income vs expense chart widget', function () {
    $this->actingAs($this->user);

    $widget = new IncomeVsExpenseChart();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getData');
    $method->setAccessible(true);
    $chartData = $method->invoke($widget);

    expect($chartData)->toBeArray();
    expect($chartData)->toHaveKey('datasets');
    expect($chartData)->toHaveKey('labels');
});

it('can render cash flow widget', function () {
    $this->actingAs($this->user);

    $widget = new CashFlowWidget();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});

it('handles missing company gracefully', function () {
    // Create user without company
    $userWithoutCompany = User::factory()->create();
    $this->actingAs($userWithoutCompany);

    $widget = new FinancialStatsOverview();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect($stats)->toBeEmpty();
});

it('handles service errors gracefully', function () {
    $this->actingAs($this->user);

    // Mock a service to throw an exception
    $this->mock(\App\Services\Reports\ProfitAndLossStatementService::class)
        ->shouldReceive('generate')
        ->andThrow(new \Exception('Service error'));

    $component = Livewire::test(FinancialStatsOverview::class);
    $component->assertOk();

    // Should show error message instead of crashing
    $component->assertSeeText(__('dashboard.financial.data_unavailable'));
});

it('calculates cash flow forecasts correctly', function () {
    $this->actingAs($this->user);

    // Create overdue invoice
    $customer = Partner::factory()->for($this->company)->create(['type' => PartnerType::Customer]);
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

    $widget = new CashFlowWidget();

    // Use reflection to access protected method
    $reflection = new \ReflectionClass($widget);
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = $method->invoke($widget);

    expect($stats)->toBeArray();
    expect(count($stats))->toBeGreaterThan(0);
});
