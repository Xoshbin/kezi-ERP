<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Models\StockQuant;
use Modules\Inventory\Services\Inventory\InventoryPerformanceMonitoringService;
use Modules\Inventory\Services\Inventory\InventoryQueryOptimizationService;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use ReflectionClass;
use Tests\TestCase;

class InventoryPerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Product $product;

    private StockLocation $location;

    private InventoryPerformanceMonitoringService $monitoringService;

    private InventoryQueryOptimizationService $optimizationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);
        $this->location = StockLocation::factory()->create(['company_id' => $this->company->id]);

        $this->monitoringService = app(InventoryPerformanceMonitoringService::class);
        $this->optimizationService = app(InventoryQueryOptimizationService::class);
    }

    public function test_can_analyze_table_sizes(): void
    {
        $tableSizes = $this->monitoringService->getTableSizes();

        expect($tableSizes)->toBeArray()
            ->and($tableSizes)->toHaveKey('stock_quants')
            ->and($tableSizes['stock_quants'])->toHaveKeys(['size_mb', 'rows']);
    }

    public function test_can_check_missing_indexes(): void
    {
        $recommendations = $this->monitoringService->checkMissingIndexes();

        expect($recommendations)->toBeArray();

        foreach ($recommendations as $rec) {
            expect($rec)->toHaveKeys(['table', 'issue', 'recommendation', 'priority']);
        }
    }

    public function test_can_analyze_fefo_query_performance(): void
    {
        // Create test data with lots
        $lot1 = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'expiration_date' => now()->addDays(10),
        ]);

        $lot2 = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'expiration_date' => now()->addDays(5), // Expires sooner (FEFO)
        ]);

        StockQuant::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'lot_id' => $lot1->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        StockQuant::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'lot_id' => $lot2->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $analysis = $this->monitoringService->analyzeQueryPatterns();

        expect($analysis)->toHaveKey('fefo_queries')
            ->and($analysis['fefo_queries'])->toHaveKeys(['execution_time_ms', 'performance_rating']);
    }

    public function test_can_get_bulk_stock_quantities(): void
    {
        // Create test stock moves for multiple products
        $product2 = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
        ]);

        // Create incoming stock moves with product lines
        $move1 = StockMove::factory()->create([
            'company_id' => $this->company->id,
            'move_type' => 'incoming',
            'status' => 'done',
        ]);

        StockMoveProductLine::factory()->create([
            'company_id' => $this->company->id,
            'stock_move_id' => $move1->id,
            'product_id' => $this->product->id,
            'to_location_id' => $this->location->id,
            'quantity' => 100,
        ]);

        $move2 = StockMove::factory()->create([
            'company_id' => $this->company->id,
            'move_type' => 'incoming',
            'status' => 'done',
        ]);

        StockMoveProductLine::factory()->create([
            'company_id' => $this->company->id,
            'stock_move_id' => $move2->id,
            'product_id' => $product2->id,
            'to_location_id' => $this->location->id,
            'quantity' => 200,
        ]);

        $productIds = [$this->product->id, $product2->id];
        $quantities = $this->optimizationService->getBulkStockQuantities(
            $this->company,
            $productIds,
            $this->location->id
        );

        expect($quantities)->toHaveCount(2);

        $productQuantity = $quantities->where('product_id', $this->product->id)->first();
        expect($productQuantity)->not->toBeNull()
            ->and($productQuantity->total_quantity)->toBe(100)
            ->and($productQuantity->total_reserved)->toBe(0)
            ->and($productQuantity->available_quantity)->toBe(100);
    }

    public function test_can_get_optimized_lot_availability(): void
    {
        // Create lots with different expiration dates
        $lot1 = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'expiration_date' => now()->addDays(5),
            'lot_code' => 'LOT001',
        ]);

        $lot2 = Lot::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'expiration_date' => now()->addDays(10),
            'lot_code' => 'LOT002',
        ]);

        $availability = $this->optimizationService->getOptimizedLotAvailability(
            $this->company,
            $this->product->id,
            $this->location->id
        );

        expect($availability)->toHaveCount(2);

        // Should be ordered by expiration date (FEFO)
        $firstLot = $availability->first();
        expect($firstLot->lot_code)->toBe('LOT001')
            ->and($firstLot->available_quantity)->toBe(45); // Mock data from service
    }

    public function test_caching_works_for_optimized_queries(): void
    {
        Cache::flush();

        StockQuant::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        // First call should cache the result
        $result1 = $this->optimizationService->getBulkStockQuantities(
            $this->company,
            [$this->product->id],
            $this->location->id
        );

        // Verify cache was created
        $cacheKey = "stock_quantities_{$this->company->id}_{$this->product->id}_{$this->location->id}";
        expect(Cache::has($cacheKey))->toBeTrue();

        // Second call should use cache (we can verify by checking query count)
        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $result2 = $this->optimizationService->getBulkStockQuantities(
            $this->company,
            [$this->product->id],
            $this->location->id
        );

        $queryCountAfter = count(DB::getQueryLog());

        // Should not have executed additional queries (used cache)
        expect($queryCountAfter)->toBe($queryCountBefore)
            ->and($result1->toArray())->toBe($result2->toArray());
    }

    public function test_can_clear_inventory_cache(): void
    {
        // Create cache entries
        $this->optimizationService->getBulkStockQuantities(
            $this->company,
            [$this->product->id]
        );

        // Verify cache exists
        $cacheKey = "stock_quantities_{$this->company->id}_{$this->product->id}_";
        expect(Cache::has($cacheKey))->toBeTrue();

        // Clear cache
        $this->optimizationService->clearInventoryCache($this->company);

        // Verify cache was cleared
        expect(Cache::has($cacheKey))->toBeFalse();
    }

    public function test_can_warm_up_cache(): void
    {
        Cache::flush();

        // Create some test data
        StockQuant::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 100,
        ]);

        // Warm up cache
        $this->optimizationService->warmUpCache($this->company);

        // Verify cache entries were created
        $cacheKey = "stock_quantities_{$this->company->id}_{$this->product->id}_";
        expect(Cache::has($cacheKey))->toBeTrue();
    }

    public function test_can_generate_performance_report(): void
    {
        $report = $this->monitoringService->generateOptimizationReport();

        expect($report)->toBeArray()
            ->and($report)->toHaveKeys([
                'timestamp',
                'overall_health',
                'table_analysis',
                'query_performance',
                'optimization_recommendations',
                'next_steps',
            ])
            ->and($report['overall_health'])->toBeIn(['excellent', 'good', 'fair', 'poor', 'unknown'])
            ->and($report['table_analysis'])->toBeArray()
            ->and($report['query_performance'])->toBeArray()
            ->and($report['optimization_recommendations'])->toBeArray()
            ->and($report['next_steps'])->toBeArray();
    }

    public function test_performance_monitoring_handles_errors_gracefully(): void
    {
        // Test with invalid table name to trigger error handling
        $reflection = new ReflectionClass($this->monitoringService);
        $method = $reflection->getMethod('analyzeTableIndexUsage');
        $method->setAccessible(true);

        $result = $method->invoke($this->monitoringService, 'non_existent_table');

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('needs_optimization')
            ->and($result['needs_optimization'])->toBeTrue();
    }

    public function test_query_optimization_service_handles_empty_data(): void
    {
        // Test with company that has no inventory data
        $emptyCompany = Company::factory()->create();

        $result = $this->optimizationService->getBulkStockQuantities(
            $emptyCompany,
            [999] // Non-existent product ID
        );

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toHaveCount(0);
    }
}
