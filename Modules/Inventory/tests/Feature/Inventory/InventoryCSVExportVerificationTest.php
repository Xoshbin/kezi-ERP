<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use Carbon\Carbon;
use Brick\Money\Money;
use Modules\Inventory\Models\Lot;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Storage;
use Tests\Traits\WithConfiguredCompany;
use Modules\Inventory\Models\StockQuant;
use Modules\Product\Enums\Products\ProductType;
use Modules\Inventory\Models\InventoryCostLayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Services\Inventory\InventoryCSVExportService;
use Modules\Inventory\Services\Inventory\InventoryReportingService;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->reportingService = app(InventoryReportingService::class);
    Storage::fake('local');

    // Create sample data for export testing
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Set up test data for CSV exports
    setupExportTestData();
});

describe('Inventory CSV Export Verification', function () {
    it('can export inventory valuation report to CSV', function () {
        $date = Carbon::now();
        $valuation = $this->reportingService->valuationAt($date, ['company_id' => test()->company->id]);

        // Generate CSV content
        $csvContent = generateValuationCSV($valuation);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThanOrEqual(1); // At least header

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'SKU', 'Quantity', 'Unit Cost', 'Total Value', 'Valuation Method');

        // If there are data rows, verify their structure
        if (count($lines) > 1) {
            $firstRow = str_getcsv($lines[1]);
            expect($firstRow)->toHaveCount(6); // 6 columns
            expect($firstRow[0])->not->toBeEmpty(); // Product name
            expect(is_numeric($firstRow[2]))->toBeTrue(); // Quantity
            expect(is_numeric(str_replace(',', '', $firstRow[3])))->toBeTrue(); // Unit Cost
            expect(is_numeric(str_replace(',', '', $firstRow[4])))->toBeTrue(); // Total Value
        }
    });

    it('can export inventory aging report to CSV', function () {
        $aging = $this->reportingService->ageing([
            'buckets' => [
                ['min' => 0, 'max' => 30, 'label' => '0-30 days'],
                ['min' => 31, 'max' => 60, 'label' => '31-60 days'],
                ['min' => 61, 'max' => 90, 'label' => '61-90 days'],
            ],
            'company_id' => test()->company->id,
        ]);

        // Generate CSV content
        $csvContent = generateAgingCSV($aging);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1); // Header + data

        // Verify header for bucket summary
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Age Bucket', 'Quantity', 'Value');
    });

    it('can export inventory turnover report to CSV', function () {
        $turnover = $this->reportingService->turnover([
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now(),
            'company_id' => test()->company->id,
        ]);

        // Generate CSV content
        $csvContent = generateTurnoverCSV($turnover);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1);

        // Verify header for summary metrics
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Metric', 'Value');
    });

    it('can export lot traceability report to CSV', function () {
        $product = test()->products->first();
        $lot = test()->lots->first();

        $traceability = $this->reportingService->lotTrace($product, $lot);

        // Generate CSV content
        $csvContent = generateLotTraceabilityCSV($traceability);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThanOrEqual(1); // At least header

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Date', 'Reference', 'Move Type', 'Quantity', 'From Location', 'To Location', 'Unit Cost');
    });

    it('can export reorder status report to CSV', function () {
        $reorderStatus = $this->reportingService->reorderStatus(['company_id' => test()->company->id]);

        // Generate CSV content
        $csvContent = generateReorderStatusCSV($reorderStatus);

        // Verify CSV structure
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(1);

        // Verify header
        $header = str_getcsv($lines[0]);
        expect($header)->toContain('Product Name', 'Location', 'Current Stock', 'Reserved', 'Available', 'Min Quantity', 'Max Quantity', 'Safety Stock', 'Suggested Quantity', 'Priority');
    });

    it('handles special characters in CSV export', function () {
        // Create product with special characters
        $specialProduct = Product::factory()->for(test()->company)->create([
            'name' => 'Product with "Quotes" & Commas, Special chars',
            'sku' => 'SPECIAL-001',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]);

        StockQuant::factory()->create([
            'company_id' => test()->company->id,
            'product_id' => $specialProduct->id,
            'location_id' => test()->stockLocation->id,
            'quantity' => 10,
        ]);

        // Create cost layer for the special product
        InventoryCostLayer::factory()->create([
            'product_id' => $specialProduct->id,
            'remaining_quantity' => 10,
            'cost_per_unit' => Money::of(5000000, 'IQD'),
            'purchase_date' => Carbon::now()->subDays(5),
        ]);

        $valuation = $this->reportingService->valuationAt(Carbon::now(), ['company_id' => test()->company->id]);
        $csvContent = generateValuationCSV($valuation);

        // Verify special characters are properly escaped
        expect($csvContent)->toContain('"Product with ""Quotes"" & Commas, Special chars"');
    });

    it('exports large datasets efficiently', function () {
        // Create many products and stock quants
        $products = Product::factory()->count(100)->for(test()->company)->create([
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]);

        foreach ($products as $product) {
            StockQuant::factory()->create([
                'company_id' => test()->company->id,
                'product_id' => $product->id,
                'location_id' => test()->stockLocation->id,
                'quantity' => rand(1, 100),
            ]);

            // Create cost layer for each product
            InventoryCostLayer::factory()->create([
                'product_id' => $product->id,
                'remaining_quantity' => rand(1, 100),
                'cost_per_unit' => Money::of(5000000, 'IQD'),
                'purchase_date' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        $startTime = microtime(true);
        $valuation = $this->reportingService->valuationAt(Carbon::now(), ['company_id' => test()->company->id]);
        $csvContent = generateValuationCSV($valuation);
        $endTime = microtime(true);

        // Should complete within reasonable time (5 seconds)
        expect($endTime - $startTime)->toBeLessThan(5.0);

        // Should have all products
        $lines = explode("\n", trim($csvContent));
        expect(count($lines))->toBeGreaterThan(100); // Header + 100+ products
    });

    it('exports with proper number formatting', function () {
        $valuation = $this->reportingService->valuationAt(Carbon::now(), ['company_id' => test()->company->id]);
        $csvContent = generateValuationCSV($valuation);

        $lines = explode("\n", trim($csvContent));

        // Only test if there are data rows
        if (count($lines) > 1 && trim($lines[1]) !== '') {
            $dataRow = str_getcsv($lines[1]);

            // Verify numeric formatting (should be parseable as numbers)
            expect(is_numeric($dataRow[2]))->toBeTrue(); // Quantity
            expect(is_numeric(str_replace(',', '', $dataRow[3])))->toBeTrue(); // Unit Cost
            expect(is_numeric(str_replace(',', '', $dataRow[4])))->toBeTrue(); // Total Value
        } else {
            // If no data, just verify the CSV structure is valid
            expect($csvContent)->toContain('Product Name');
        }
    });

    it('includes proper metadata in CSV exports', function () {
        $valuation = $this->reportingService->valuationAt(Carbon::now(), ['company_id' => test()->company->id]);
        $csvContent = generateValuationCSV($valuation, true); // Include metadata

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
    $csvService = app(InventoryCSVExportService::class);
    return $csvService->exportValuationReport($valuation, ['include_metadata' => $includeMetadata]);
}

function generateAgingCSV(array $aging): string
{
    $csvService = app(InventoryCSVExportService::class);
    return $csvService->exportAgingReport($aging);
}

function generateTurnoverCSV(array $turnover): string
{
    $csvService = app(InventoryCSVExportService::class);
    return $csvService->exportTurnoverReport($turnover);
}

function generateLotTraceabilityCSV(array $traceability): string
{
    $csv = "Date,Reference,Move Type,Quantity,From Location,To Location,Unit Cost,Status\n";

    if (isset($traceability['movements']) && is_array($traceability['movements'])) {
        foreach ($traceability['movements'] as $movement) {
            $csv .= sprintf(
                '"%s","%s","%s",%s,"%s","%s",%s,"%s"' . "\n",
                $movement['move_date'] ?? '',
                $movement['reference'] ?? '',
                $movement['move_type'] ?? '',
                number_format($movement['quantity'] ?? 0, 4),
                $movement['from_location_name'] ?? '',
                $movement['to_location_name'] ?? '',
                isset($movement['valuation_amount']) ? number_format($movement['valuation_amount']->getAmount()->toFloat() / 1000, 2) : '0.00',
                $movement['status'] ?? ''
            );
        }
    }

    return $csv;
}

function generateReorderStatusCSV(array $reorderStatus): string
{
    $csvService = app(InventoryCSVExportService::class);
    return $csvService->exportReorderStatusReport($reorderStatus);
}

function setupExportTestData(): void
{
    // Create test products
    test()->products = collect([
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product A',
            'sku' => 'TEST-A',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::FIFO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]),
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product B',
            'sku' => 'TEST-B',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
            'inventory_valuation_method' => ValuationMethod::AVCO,
            'default_inventory_account_id' => test()->inventoryAccount->id,
        ]),
        Product::factory()->for(test()->company)->create([
            'name' => 'Test Product C',
            'sku' => 'TEST-C',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
            'location_id' => test()->stockLocation->id,
            'quantity' => 100 + ($index * 50),
            'reserved_quantity' => 10 + ($index * 5),
        ]);

        // Create cost layers
        InventoryCostLayer::factory()->create([
            'product_id' => $product->id,
            'remaining_quantity' => 100 + ($index * 50),
            'cost_per_unit' => Money::of(10000000 + ($index * 1000000), 'IQD'),
            'purchase_date' => Carbon::now()->subDays($index * 10),
        ]);
    }
}
