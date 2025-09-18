<?php

namespace Tests\Feature\Inventory;

use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Models\InventoryCostLayer;
use App\Models\Lot;
use App\Models\Product;
use App\Models\StockQuant;
use App\Services\Inventory\InventoryReportingService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->reportingService = app(InventoryReportingService::class);
    Storage::fake('local');

    // Create sample data for export testing
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();
});

describe('Inventory CSV Export Verification', function () {
    it('can export inventory valuation report to CSV', function () {
        $date = Carbon::now();
        $valuation = $this->reportingService->valuationAt($date);

        // Generate CSV content
        $csvContent = $this->generateValuationCSV($valuation);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect($lines)->toHaveCount(4); // Header + 3 products

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'SKU', 'Quantity', 'Unit Cost', 'Total Value', 'Valuation Method');

        // Verify data rows
        $firstRow = str_getcsv($lines[1]);
        expect($firstRow[0])->toBe('Test Product A'); // Product name
        expect($firstRow[1])->toBe('TEST-A'); // SKU
        expect((float) $firstRow[2])->toBe(100.0); // Quantity
        expect($firstRow[5])->toBe('FIFO'); // Valuation method
    });

    it('can export inventory aging report to CSV', function () {
        $aging = $this->reportingService->ageing([30, 60, 90]);

        // Generate CSV content
        $csvContent = $this->generateAgingCSV($aging);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1); // Header + data

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'Total Quantity', 'Total Value', '0-30 Days', '31-60 Days', '61-90 Days', '90+ Days');
    });

    it('can export inventory turnover report to CSV', function () {
        $turnover = $this->reportingService->turnover(
            Carbon::now()->subDays(30),
            Carbon::now()
        );

        // Generate CSV content
        $csvContent = $this->generateTurnoverCSV($turnover);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1);

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'Average Inventory', 'COGS', 'Turnover Ratio', 'Days Sales Outstanding');
    });

    it('can export lot traceability report to CSV', function () {
        $product = $this->products->first();
        $lot = $this->lots->first();

        $traceability = $this->reportingService->lotTrace($product, $lot);

        // Generate CSV content
        $csvContent = $this->generateLotTraceabilityCSV($traceability);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1);

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Date', 'Reference', 'Move Type', 'Quantity', 'From Location', 'To Location', 'Unit Cost');
    });

    it('can export reorder status report to CSV', function () {
        $reorderStatus = $this->reportingService->reorderStatus();

        // Generate CSV content
        $csvContent = $this->generateReorderStatusCSV($reorderStatus);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1);

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'Current Stock', 'Reserved', 'Available', 'Min Quantity', 'Max Quantity', 'Reorder Quantity', 'Status');
    });

    it('handles special characters in CSV export', function () {
        // Create product with special characters
        $specialProduct = Product::factory()->for($this->company)->create([
            'name' => 'Product with "Quotes" & Commas, Special chars',
            'sku' => 'SPECIAL-001',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'default_inventory_account_id' => $this->inventoryAccount->id,
        ]);

        StockQuant::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $specialProduct->id,
            'location_id' => $this->warehouseLocation->id,
            'quantity' => 10,
        ]);

        $valuation = $this->reportingService->valuationAt(Carbon::now());
        $csvContent = $this->generateValuationCSV($valuation);

        // Verify special characters are properly escaped
        expect($csvContent)->toContain('"Product with ""Quotes"" & Commas, Special chars"');
    });

    it('exports large datasets efficiently', function () {
        // Create many products and stock quants
        $products = Product::factory()->count(100)->for($this->company)->create([
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'default_inventory_account_id' => $this->inventoryAccount->id,
        ]);

        foreach ($products as $product) {
            StockQuant::factory()->create([
                'company_id' => $this->company->id,
                'product_id' => $product->id,
                'location_id' => $this->warehouseLocation->id,
                'quantity' => rand(1, 100),
            ]);
        }

        $startTime = microtime(true);
        $valuation = $this->reportingService->valuationAt(Carbon::now());
        $csvContent = $this->generateValuationCSV($valuation);
        $endTime = microtime(true);

        // Should complete within reasonable time (5 seconds)
        expect($endTime - $startTime)->toBeLessThan(5.0);

        // Should have all products
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(100); // Header + 100+ products
    });

    it('exports with proper number formatting', function () {
        $valuation = $this->reportingService->valuationAt(Carbon::now());
        $csvContent = $this->generateValuationCSV($valuation);

        $lines = explode("\n", trim($csvContent));
        $dataRow = str_getcsv($lines[1]);

        // Verify numeric formatting (should be parseable as numbers)
        expect(is_numeric($dataRow[2]))->toBeTrue(); // Quantity
        expect(is_numeric(str_replace(',', '', $dataRow[3])))->toBeTrue(); // Unit Cost
        expect(is_numeric(str_replace(',', '', $dataRow[4])))->toBeTrue(); // Total Value
    });

    it('includes proper metadata in CSV exports', function () {
        $valuation = $this->reportingService->valuationAt(Carbon::now());
        $csvContent = $this->generateValuationCSV($valuation, true); // Include metadata

        $lines = explode("\n", $csvContent);

        // Should include metadata at the top
        expect($lines[0])->toContain('Inventory Valuation Report');
        expect($lines[1])->toContain('Generated on:');
        expect($lines[2])->toContain('Company:');
        expect($lines[3])->toContain('As of Date:');

        // Data should start after metadata
        $headerLine = null;
        foreach ($lines as $index => $line) {
            if (str_contains($line, 'Product Name')) {
                $headerLine = $index;
                break;
            }
        }

        expect($headerLine)->not->toBeNull();
        expect($headerLine)->toBeGreaterThan(3);
    });
});

// Helper methods for CSV generation
function generateValuationCSV(array $valuation, bool $includeMetadata = false): string
{
    $csv = '';

    if ($includeMetadata) {
        $csv .= "Inventory Valuation Report\n";
        $csv .= "Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Company: " . test()->company->name . "\n";
        $csv .= "As of Date: " . Carbon::now()->format('Y-m-d') . "\n";
        $csv .= "\n";
    }

    // Header
    $csv .= "Product Name,SKU,Quantity,Unit Cost,Total Value,Valuation Method\n";

    // Data rows
    foreach ($valuation['products'] as $product) {
        $csv .= sprintf(
            '"%s","%s",%s,%s,%s,"%s"' . "\n",
            str_replace('"', '""', $product['product_name']),
            $product['product_sku'] ?? '',
            number_format($product['total_quantity'], 4),
            number_format($product['average_cost']->getAmount()->toFloat() / 1000, 2),
            number_format($product['total_value']->getAmount()->toFloat() / 1000, 2),
            $product['valuation_method'] ?? 'N/A'
        );
    }

    return $csv;
}

function generateAgingCSV(array $aging): string
{
    $csv = "Product Name,SKU,Total Quantity,Total Value,0-30 Days,31-60 Days,61-90 Days,90+ Days\n";

    foreach ($aging['products'] as $product) {
        $csv .= sprintf(
            '"%s","%s",%s,%s,%s,%s,%s,%s' . "\n",
            str_replace('"', '""', $product['product_name']),
            $product['product_sku'] ?? '',
            number_format($product['total_quantity'], 4),
            number_format($product['total_value']->getAmount()->toFloat() / 1000, 2),
            number_format($product['buckets'][0]['value']->getAmount()->toFloat() / 1000, 2),
            number_format($product['buckets'][1]['value']->getAmount()->toFloat() / 1000, 2),
            number_format($product['buckets'][2]['value']->getAmount()->toFloat() / 1000, 2),
            number_format($product['buckets'][3]['value']->getAmount()->toFloat() / 1000, 2)
        );
    }

    return $csv;
}

function generateTurnoverCSV(array $turnover): string
{
    $csv = "Product Name,SKU,Average Inventory,COGS,Turnover Ratio,Days Sales Outstanding\n";

    foreach ($turnover['products'] as $product) {
        $csv .= sprintf(
            '"%s","%s",%s,%s,%s,%s' . "\n",
            str_replace('"', '""', $product['product_name']),
            $product['product_sku'] ?? '',
            number_format($product['average_inventory_value']->getAmount()->toFloat() / 1000, 2),
            number_format($product['cogs']->getAmount()->toFloat() / 1000, 2),
            number_format($product['turnover_ratio'], 2),
            number_format($product['days_sales_outstanding'], 0)
        );
    }

    return $csv;
}

function generateLotTraceabilityCSV(array $traceability): string
{
    $csv = "Date,Reference,Move Type,Quantity,From Location,To Location,Unit Cost,Status\n";

    foreach ($traceability['movements'] as $movement) {
        $csv .= sprintf(
            '"%s","%s","%s",%s,"%s","%s",%s,"%s"' . "\n",
            $movement['move_date'],
            $movement['reference'],
            $movement['move_type'],
            number_format($movement['quantity'], 4),
            $movement['from_location_name'] ?? '',
            $movement['to_location_name'] ?? '',
            number_format($movement['valuation_amount']->getAmount()->toFloat() / 1000, 2),
            $movement['status']
        );
    }

    return $csv;
}

function generateReorderStatusCSV(array $reorderStatus): string
{
    $csv = "Product Name,SKU,Current Stock,Reserved,Available,Min Quantity,Max Quantity,Reorder Quantity,Status\n";

    foreach ($reorderStatus['products'] as $product) {
        $status = $product['current_quantity'] < $product['min_quantity'] ? 'Below Minimum' : 'OK';

        $csv .= sprintf(
            '"%s","%s",%s,%s,%s,%s,%s,%s,"%s"' . "\n",
            str_replace('"', '""', $product['product_name']),
            $product['product_sku'] ?? '',
            number_format($product['current_quantity'], 4),
            number_format($product['reserved_quantity'], 4),
            number_format($product['available_quantity'], 4),
            number_format($product['min_quantity'], 4),
            number_format($product['max_quantity'], 4),
            number_format($product['reorder_quantity'], 4),
            $status
        );
    }

    return $csv;
}

function setupExportTestData(): void
{
    // Create test products
    test()->products = collect([
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product A',
            'sku' => 'TEST-A',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]),
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product B',
            'sku' => 'TEST-B',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]),
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product C',
            'sku' => 'TEST-C',
            'type' => ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::LIFO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]),
    ]);

    // Create test lots
    test()->lots = collect([
        Lot::factory()->for(test()->company)->for(test()->products->first())->create([
            'lot_code' => 'LOT-A-001',
        ]),
    ]);

    // Create stock quants
    foreach (test()->products as $index => $product) {
        StockQuant::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => $product->id,
            'location_id' => test()->warehouseLocation->id,
            'quantity' => 100 + ($index * 50),
            'reserved_quantity' => 10 + ($index * 5),
        ]);

        // Create cost layers
        InventoryCostLayer::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => $product->id,
            'remaining_quantity' => 100 + ($index * 50),
            'cost_per_unit' => Money::of(10000000 + ($index * 1000000), 'IQD'),
            'purchase_date' => Carbon::now()->subDays($index * 10),
        ]);
    }
}
