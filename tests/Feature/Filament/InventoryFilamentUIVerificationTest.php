<?php

namespace Tests\Feature\Filament;

use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Filament\Clusters\Inventory\Pages\InventoryAgingReport;
use App\Filament\Clusters\Inventory\Pages\InventoryOverview;
use App\Filament\Clusters\Inventory\Pages\InventoryLotTraceabilityReport;
use App\Filament\Clusters\Inventory\Pages\InventoryReorderStatusReport;
use App\Filament\Clusters\Inventory\Pages\InventoryTurnoverReport;
use App\Filament\Clusters\Inventory\Pages\InventoryValuationReport;
use App\Filament\Clusters\Inventory\Resources\StockQuantResource;
use App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages\ListStockQuants;
use App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages\ViewStockQuant;
use App\Filament\Clusters\Inventory\Widgets\InventoryAgingChartWidget;
use App\Filament\Clusters\Inventory\Widgets\InventoryStatsOverviewWidget;
use App\Filament\Clusters\Inventory\Widgets\InventoryTurnoverChartWidget;
use App\Filament\Clusters\Inventory\Widgets\InventoryValueChartWidget;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockQuant;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Actions\Testing\TestsActions;
use Filament\Forms\Testing\TestsForms;
use Filament\Infolists\Testing\TestsInfolists;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
    $this->actingAs($this->user);

    // Create sample inventory data for UI testing
    setupSampleInventoryData();
});

describe('Filament Inventory UI Verification', function () {
    it('can render inventory dashboard with widgets', function () {
        Livewire::test(InventoryOverview::class)
            ->assertSuccessful()
            ->assertSee('Inventory Dashboard')
            ->assertSee('Total Inventory Value')
            ->assertSee('Low Stock Alerts')
            ->assertSee('Expiring Lots');
    });

    it('can render inventory stats overview widget', function () {
        Livewire::test(InventoryStatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Total Inventory Value')
            ->assertSee('Turnover Ratio')
            ->assertSee('Low Stock Alerts')
            ->assertSee('Expiring Lots');
    });

    it('can render inventory value chart widget', function () {
        Livewire::test(InventoryValueChartWidget::class)
            ->assertSuccessful();
    });

    it('can render inventory turnover chart widget', function () {
        Livewire::test(InventoryTurnoverChartWidget::class)
            ->assertSuccessful();
    });

    it('can render inventory aging chart widget', function () {
        Livewire::test(InventoryAgingChartWidget::class)
            ->assertSuccessful();
    });

    it('can list stock quants with proper data', function () {
        Livewire::test(ListStockQuants::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($this->stockQuants)
            ->assertTableColumnExists('product.name')
            ->assertTableColumnExists('location.name')
            ->assertTableColumnExists('quantity')
            ->assertTableColumnExists('reserved_quantity')
            ->assertTableColumnExists('available_quantity');
    });

    it('can view stock quant details', function () {
        $stockQuant = $this->stockQuants->first();

        Livewire::test(ViewStockQuant::class, ['record' => $stockQuant->getRouteKey()])
            ->assertSuccessful()
            ->assertSee($stockQuant->product->name)
            ->assertSee($stockQuant->location->name)
            ->assertSee(number_format($stockQuant->quantity, 4));
    });

    it('can filter stock quants by product', function () {
        $product = $this->products->first();

        Livewire::test(ListStockQuants::class)
            ->filterTable('product', $product->id)
            ->assertCanSeeTableRecords(
                $this->stockQuants->where('product_id', $product->id)
            );
    });

    it('can filter stock quants by location', function () {
        Livewire::test(ListStockQuants::class)
            ->filterTable('location', $this->stockLocation->id)
            ->assertCanSeeTableRecords(
                $this->stockQuants->where('location_id', $this->stockLocation->id)
            );
    });

    it('can filter out of stock items', function () {
        Livewire::test(ListStockQuants::class)
            ->filterTable('out_of_stock')
            ->assertSuccessful();
    });

    it('can render inventory valuation report page', function () {
        Livewire::test(InventoryValuationReport::class)
            ->assertSuccessful()
            ->assertSee('Inventory Valuation Report')
            ->assertFormExists();
    });

    it('can generate inventory valuation report', function () {
        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => Carbon::now()->format('Y-m-d'),
                'include_reconciliation' => true,
            ])
            ->call('generateReport')
            ->assertSuccessful()
            ->assertSee('Total Inventory Value');
    });

    it('can render inventory aging report page', function () {
        Livewire::test(InventoryAgingReport::class)
            ->assertSuccessful()
            ->assertSee('Inventory Aging Report')
            ->assertFormExists();
    });

    it('can generate inventory aging report', function () {
        Livewire::test(InventoryAgingReport::class)
            ->fillForm([
                'buckets' => [30, 60, 90],
            ])
            ->call('generateReport')
            ->assertSuccessful();
    });

    it('can render inventory turnover report page', function () {
        Livewire::test(InventoryTurnoverReport::class)
            ->assertSuccessful()
            ->assertSee('Inventory Turnover Report')
            ->assertFormExists();
    });

    it('can generate inventory turnover report', function () {
        Livewire::test(InventoryTurnoverReport::class)
            ->fillForm([
                'date_from' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'date_to' => Carbon::now()->format('Y-m-d'),
            ])
            ->call('generateReport')
            ->assertSuccessful();
    });

    it('can render lot traceability report page', function () {
        Livewire::test(InventoryLotTraceabilityReport::class)
            ->assertSuccessful()
            ->assertSee('Lot Traceability Report')
            ->assertFormExists();
    });

    it('can generate lot traceability report', function () {
        $lot = $this->lots->first();

        Livewire::test(InventoryLotTraceabilityReport::class)
            ->fillForm([
                'product_id' => $lot->product_id,
                'lot_id' => $lot->id,
            ])
            ->call('generateReport')
            ->assertSuccessful()
            ->assertSee($lot->lot_code);
    });

    it('can render reorder status report page', function () {
        Livewire::test(InventoryReorderStatusReport::class)
            ->assertSuccessful()
            ->assertSee('Reorder Status Report')
            ->assertFormExists();
    });

    it('can generate reorder status report', function () {
        Livewire::test(InventoryReorderStatusReport::class)
            ->call('generateReport')
            ->assertSuccessful();
    });

    it('can export reports (placeholder functionality)', function () {
        // Test export action exists and can be triggered
        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->call('generateReport')
            ->callAction('export')
            ->assertSuccessful();
    });

    it('can refresh reports', function () {
        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->callAction('refresh')
            ->assertSuccessful();
    });

    it('validates form inputs correctly', function () {
        // Test date validation
        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => 'invalid-date',
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['as_of_date']);

        // Test required fields
        Livewire::test(InventoryTurnoverReport::class)
            ->fillForm([
                'date_from' => '',
                'date_to' => '',
            ])
            ->call('generateReport')
            ->assertHasFormErrors(['date_from', 'date_to']);
    });

    it('handles empty data gracefully', function () {
        // Clear all stock quants
        StockQuant::query()->delete();

        Livewire::test(ListStockQuants::class)
            ->assertSuccessful()
            ->assertSee('No stock quants found');

        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->call('generateReport')
            ->assertSuccessful();
    });

    it('displays proper currency formatting', function () {
        Livewire::test(InventoryValuationReport::class)
            ->fillForm([
                'as_of_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->call('generateReport')
            ->assertSuccessful()
            ->assertSee('IQD'); // Should show currency code
    });

    it('shows lot information when available', function () {
        $stockQuantWithLot = $this->stockQuants->whereNotNull('lot_id')->first();

        if ($stockQuantWithLot) {
            Livewire::test(ViewStockQuant::class, ['record' => $stockQuantWithLot->getRouteKey()])
                ->assertSuccessful()
                ->assertSee($stockQuantWithLot->lot->lot_code);
        } else {
            // If no lot data, just verify the test setup
            expect($this->lots)->toHaveCount(2);
        }
    });
});

// Helper method to set up sample inventory data
function setupSampleInventoryData(): void
{
    // Create sample products
    test()->products = collect([
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Laptop',
            'sku' => 'TEST-LAPTOP-001',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'unit_price' => Money::of(150000000, 'IQD'),
            'default_inventory_account_id' => test()->inventoryAccount->id,
            'default_stock_input_account_id' => test()->stockInputAccount->id,
            'default_cogs_account_id' => test()->cogsAccount->id,
            'average_cost' => Money::of(0, 'IQD'),
        ]),
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Smartphone',
            'sku' => 'TEST-PHONE-001',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'unit_price' => Money::of(120000000, 'IQD'),
            'default_inventory_account_id' => test()->inventoryAccount->id,
            'default_stock_input_account_id' => test()->stockInputAccount->id,
            'default_cogs_account_id' => test()->cogsAccount->id,
            'average_cost' => Money::of(0, 'IQD'),
        ]),
    ]);

    // Create sample lots
    test()->lots = collect([
        Lot::factory()->for(test()->company)->for(test()->products->first())->create([
            'lot_code' => 'LOT-TEST-001',
            'expiration_date' => Carbon::now()->addMonths(6),
        ]),
        Lot::factory()->for(test()->company)->for(test()->products->last())->create([
            'lot_code' => 'LOT-TEST-002',
            'expiration_date' => Carbon::now()->addMonths(12),
        ]),
    ]);

    // Create sample stock quants
    test()->stockQuants = collect([
        StockQuant::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => test()->products->first()->id,
            'location_id' => test()->stockLocation->id,
            'lot_id' => test()->lots->first()->id,
            'quantity' => 50,
            'reserved_quantity' => 10,
        ]),
        StockQuant::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => test()->products->last()->id,
            'location_id' => test()->stockLocation->id,
            'lot_id' => test()->lots->last()->id,
            'quantity' => 30,
            'reserved_quantity' => 5,
        ]),
        StockQuant::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => test()->products->first()->id,
            'location_id' => test()->stockLocation->id,
            'lot_id' => null,
            'quantity' => 25,
            'reserved_quantity' => 0,
        ]),
    ]);
}
