<?php

namespace Kezi\Inventory\Services\Inventory;

use App\Models\Company;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\InventoryCostLayer;
use Kezi\Inventory\Models\Lot;
use Kezi\Inventory\Models\ReorderingRule;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Models\Product;
use RuntimeException;

/**
 * Inventory Reporting Service
 *
 * This service provides comprehensive inventory reporting capabilities including valuation reports,
 * aging analysis, turnover calculations, lot traceability, and reorder status reporting.
 *
 * Key Features:
 * - Point-in-time inventory valuation using multiple methods
 * - Inventory aging analysis with configurable buckets
 * - Turnover ratio calculations and trend analysis
 * - Complete lot traceability (forward and backward)
 * - Reorder status and replenishment suggestions
 * - GL reconciliation for inventory accounts
 *
 * Reporting Capabilities:
 * - Valuation reports with cost layer details
 * - Aging reports with expiration tracking
 * - Turnover analysis with performance metrics
 * - Lot traceability with complete movement history
 * - Reorder status with priority ranking
 *
 * Data Sources:
 * - StockMoveValuation for historical valuations
 * - InventoryCostLayer for FIFO/LIFO cost tracking
 * - StockQuant for current quantities
 * - ReorderingRule for replenishment logic
 *
 * @author Laravel/Filament Inventory System
 *
 * @version 1.0.0
 */
class InventoryReportingService
{
    /**
     * Create a new inventory reporting service instance
     *
     * @param  StockQuantService  $stockQuantService  Service for stock quantity operations
     * @param  ReorderingRuleService  $reorderingRuleService  Service for reordering logic
     */
    public function __construct(
        private readonly StockQuantService $stockQuantService,
        private readonly ReorderingRuleService $reorderingRuleService,
    ) {}

    /**
     * Generate inventory valuation report as of a specific date
     *
     * This method calculates the total inventory value as of a specific date using
     * the appropriate valuation method for each product. It considers all stock movements
     * up to the specified date and provides detailed valuation information.
     *
     * The report includes:
     * - Product-level valuation details
     * - Quantity on hand and reserved quantities
     * - Unit costs based on valuation method
     * - Total values and summary statistics
     * - Cost layer details for FIFO/LIFO products
     *
     * @param  Carbon  $asOfDate  The date for valuation calculation
     * @param  array  $filters  Optional filters for products, locations, etc.
     *                          - company_id: Specific company (required)
     *                          - product_ids: Array of specific product IDs
     *                          - location_ids: Array of specific location IDs
     *                          - include_zero_qty: Include products with zero quantity
     * @return array Valuation report data structure containing:
     *               - products: Array of product valuation details
     *               - total_value: Total inventory value
     *               - total_quantity: Total quantity across all products
     *               - summary_by_method: Summary grouped by valuation method
     *               - as_of_date: Report date
     *
     * @example
     * $valuation = $service->valuationAt(Carbon::now(), [
     *     'company_id' => 1,
     *     'product_ids' => [123, 456],
     *     'include_zero_qty' => false
     * ]);
     */
    public function valuationAt(Carbon $asOfDate, array $filters = []): array
    {
        $company = $this->getCompanyFromFilters($filters);

        // Get all stock move valuations up to the date
        $valuations = StockMoveValuation::query()
            ->where('company_id', $company->id)
            ->whereHas('stockMove', function (Builder $query) use ($asOfDate) {
                $query->where('move_date', '<=', $asOfDate)
                    ->where('status', 'done');
            })
            ->with(['product', 'stockMove'])
            ->get();

        // Get cost layers for FIFO/LIFO products as of date
        $costLayers = InventoryCostLayer::query()
            ->whereHas('product', function (Builder $query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->where('purchase_date', '<=', $asOfDate)
            ->where('remaining_quantity', '>', 0)
            ->with(['product'])
            ->get();

        $totalValue = Money::of(0, $company->currency->code);
        $totalQuantity = 0.0;
        $byProduct = [];

        // Process by product
        $products = Product::where('company_id', $company->id)
            ->where('type', 'storable')
            ->when(isset($filters['product_ids']), function (Builder $query) use ($filters) {
                $query->whereIn('id', $filters['product_ids']);
            })
            ->get();

        foreach ($products as $product) {
            $productValuation = $this->calculateProductValuation($product, $asOfDate, $valuations, $costLayers, $filters);

            if ($productValuation['quantity'] > 0) {
                $byProduct[$product->id] = $productValuation;
                $totalValue = $totalValue->plus($productValuation['value']);
                $totalQuantity += $productValuation['quantity'];
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'total_value' => $totalValue,
            'total_quantity' => $totalQuantity,
            'by_product' => $byProduct,
            'currency' => $company->currency->code,
        ];
    }

    /**
     * Reconcile inventory valuation with GL account balances
     */
    public function reconcileWithGL(Carbon $asOfDate, array $filters = []): array
    {
        $company = $this->getCompanyFromFilters($filters);

        // Get inventory account balance from journal entries
        $inventoryAccountBalance = $this->getInventoryAccountBalance($company, $asOfDate);

        // Get valuation from our calculation
        $valuation = $this->valuationAt($asOfDate, $filters);

        $difference = $inventoryAccountBalance->minus($valuation['total_value']);

        return [
            'inventory_account_balance' => $inventoryAccountBalance,
            'calculated_valuation' => $valuation['total_value'],
            'reconciliation_difference' => $difference,
            'is_reconciled' => $difference->isZero(),
            'as_of_date' => $asOfDate,
        ];
    }

    /**
     * Generate aging report for inventory
     */
    public function ageing(array $options = []): array
    {
        $company = $this->getCompanyFromFilters($options);
        $buckets = $options['buckets'] ?? $this->getDefaultAgingBuckets();
        $includeExpiration = $options['include_expiration'] ?? false;
        $expirationWarningDays = $options['expiration_warning_days'] ?? 30;

        $result = [
            'buckets' => [],
            'total_quantity' => 0.0,
            'total_value' => Money::of(0, $company->currency->code),
        ];

        // Initialize buckets
        foreach ($buckets as $bucket) {
            $result['buckets'][$bucket['label']] = [
                'quantity' => 0.0,
                'value' => Money::of(0, $company->currency->code),
                'products' => [],
            ];
        }

        // Get cost layers with age calculation
        $costLayers = InventoryCostLayer::query()
            ->whereHas('product', function (Builder $query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->where('remaining_quantity', '>', 0)
            ->with(['product'])
            ->get();

        foreach ($costLayers as $layer) {
            $ageInDays = $layer->purchase_date->diffInDays(now());
            $bucket = $this->findAgingBucket($ageInDays, $buckets);

            if ($bucket) {
                $layerValue = $layer->cost_per_unit->multipliedBy($layer->remaining_quantity);

                $result['buckets'][$bucket['label']]['quantity'] += $layer->remaining_quantity;
                $result['buckets'][$bucket['label']]['value'] = $result['buckets'][$bucket['label']]['value']->plus($layerValue);

                $result['total_quantity'] += $layer->remaining_quantity;
                $result['total_value'] = $result['total_value']->plus($layerValue);
            }
        }

        // Add expiration information if requested
        if ($includeExpiration) {
            $result['expiring_soon'] = $this->getExpiringLots($company, $expirationWarningDays);
        }

        return $result;
    }

    /**
     * Generate inventory turnover report
     */
    public function turnover(array $options = []): array
    {
        $company = $this->getCompanyFromFilters($options);
        $startDate = $options['start_date'] ?? Carbon::now()->startOfYear();
        $endDate = $options['end_date'] ?? Carbon::now()->endOfYear();

        // Get COGS for the period
        $totalCogs = $this->getCOGSForPeriod($company, $startDate, $endDate);

        // Get average inventory value
        $averageInventoryValue = $this->getAverageInventoryValue($company, $startDate, $endDate);

        // Calculate turnover metrics
        $inventoryTurnoverRatio = $averageInventoryValue->isZero() ? 0 :
            (float) $totalCogs->getAmount()->dividedBy($averageInventoryValue->getAmount(), 4, RoundingMode::HALF_UP)->toFloat();

        $daysSalesInventory = $inventoryTurnoverRatio > 0 ?
            365 / $inventoryTurnoverRatio : 0;

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_cogs' => $totalCogs,
            'average_inventory_value' => $averageInventoryValue,
            'inventory_turnover_ratio' => $inventoryTurnoverRatio,
            'days_sales_inventory' => $daysSalesInventory,
            'currency' => $company->currency->code,
        ];
    }

    /**
     * Generate lot traceability report
     */
    public function lotTrace(Product $product, Lot $lot): array
    {
        // Get all stock moves for this lot through product lines
        $movements = StockMove::query()
            ->whereHas('productLines', function (Builder $query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->whereHas('stockMoveLines', function (Builder $query) use ($lot) {
                $query->where('lot_id', $lot->id);
            })
            ->with(['stockMoveLines' => function ($query) use ($lot) {
                $query->where('lot_id', $lot->id);
            }, 'productLines' => function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->with(['fromLocation', 'toLocation']);
            }, 'stockMoveValuations'])
            ->orderBy('move_date')
            ->get();

        $currentQuantity = 0.0;
        $totalValue = Money::of(0, $product->company->currency->code);
        $movementHistory = [];

        foreach ($movements as $move) {
            $moveQuantity = $move->stockMoveLines->sum('quantity');

            if ($move->move_type === StockMoveType::Incoming) {
                $currentQuantity += $moveQuantity;
            } else {
                $currentQuantity -= $moveQuantity;
            }

            // Get location names from the product line for this product
            $productLine = $move->productLines->first();

            $movementHistory[] = [
                'move_date' => $move->move_date,
                'move_type' => $move->move_type,
                'quantity' => $moveQuantity,
                'from_location' => $productLine?->fromLocation?->name,
                'to_location' => $productLine?->toLocation?->name,
                'reference' => $move->reference,
                'journal_entry_id' => $move->stockMoveValuations->first()?->journal_entry_id,
                'valuation_amount' => $move->stockMoveValuations->first()?->cost_impact,
            ];
        }

        // Calculate current value based on remaining quantity
        $remainingQuantityForValue = $currentQuantity;
        if ($remainingQuantityForValue > 0) {
            $costLayers = InventoryCostLayer::where('product_id', $product->id)
                ->where('remaining_quantity', '>', 0)
                ->get();

            foreach ($costLayers as $layer) {
                $layerValue = $layer->cost_per_unit->multipliedBy(min($remainingQuantityForValue, $layer->remaining_quantity));
                $totalValue = $totalValue->plus($layerValue);
                $remainingQuantityForValue -= min($remainingQuantityForValue, $layer->remaining_quantity);

                if ($remainingQuantityForValue <= 0) {
                    break;
                }
            }
        }

        return [
            'lot_code' => $lot->lot_code,
            'product_name' => $product->name,
            'expiration_date' => $lot->expiration_date,
            'current_quantity' => $currentQuantity,
            'total_value' => $totalValue,
            'movements' => $movementHistory,
        ];
    }

    /**
     * Get reorder status for all products with reordering rules
     */
    public function reorderStatus(array $filters = []): array
    {
        $company = $this->getCompanyFromFilters($filters);

        $belowMinimum = [];
        $totalOnHand = 0.0;
        $totalReserved = 0.0;
        $totalAvailable = 0.0;

        $reorderingRules = ReorderingRule::where('company_id', $company->id)
            ->where('active', true)
            ->with(['product', 'location'])
            ->get();

        foreach ($reorderingRules as $rule) {
            $currentQty = $this->stockQuantService->getTotalQuantity(
                $company->id,
                $rule->product_id,
                $rule->location_id
            );

            $reservedQty = $this->stockQuantService->getReservedQuantity(
                $company->id,
                $rule->product_id,
                $rule->location_id
            );

            $availableQty = $currentQty - $reservedQty;

            $totalOnHand += $currentQty;
            $totalReserved += $reservedQty;
            $totalAvailable += $availableQty;

            if ($currentQty < $rule->min_qty || $availableQty < $rule->safety_stock) {
                $suggestedQty = $rule->calculateSuggestedQuantity($currentQty);
                $priority = $rule->determinePriority($availableQty);

                $belowMinimum[] = [
                    'product_id' => $rule->product_id,
                    'product_name' => $rule->product->name,
                    'location_name' => $rule->location->name,
                    'current_qty' => $currentQty,
                    'reserved_qty' => $reservedQty,
                    'available_qty' => $availableQty,
                    'min_qty' => $rule->min_qty,
                    'max_qty' => $rule->max_qty,
                    'safety_stock' => $rule->safety_stock,
                    'suggested_qty' => $suggestedQty,
                    'priority' => $priority,
                    'reordering_rule_id' => $rule->id,
                ];
            }
        }

        return [
            'below_minimum' => $belowMinimum,
            'summary' => [
                'total_on_hand' => $totalOnHand,
                'total_reserved' => $totalReserved,
                'total_available' => $totalAvailable,
                'reorder_warnings_count' => count($belowMinimum),
            ],
        ];
    }

    /**
     * Get company from filters or current tenant
     */
    private function getCompanyFromFilters(array $filters): Company
    {
        if (isset($filters['company_id'])) {
            return Company::findOrFail($filters['company_id']);
        }

        // Get from current tenant context
        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new RuntimeException('Company context is required for inventory reporting');
        }

        return $company;
    }

    /**
     * Calculate valuation for a specific product
     */
    private function calculateProductValuation(
        Product $product,
        Carbon $asOfDate,
        Collection $valuations,
        Collection $costLayers,
        array $filters,
    ): array {
        $productValuations = $valuations->where('product_id', $product->id);
        $productCostLayers = $costLayers->where('product_id', $product->id);

        $quantity = 0.0;
        $value = Money::of(0, $product->company->currency->code);
        $layers = [];

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            // For AVCO, calculate historical average cost as of the date
            $incomingValuations = $productValuations->where('move_type', StockMoveType::Incoming)->sortBy('stockMove.move_date');
            $outgoingValuations = $productValuations->where('move_type', StockMoveType::Outgoing)->sortBy('stockMove.move_date');

            $runningQuantity = 0.0;
            $runningValue = Money::of(0, $product->company->currency->code);

            // Process incoming stock to build up average cost
            foreach ($incomingValuations as $valuation) {
                $incomingValue = $valuation->cost_impact;
                $incomingQuantity = $valuation->quantity;

                $runningValue = $runningValue->plus($incomingValue);
                $runningQuantity += $incomingQuantity;
            }

            // Process outgoing stock to reduce quantity
            foreach ($outgoingValuations as $valuation) {
                $runningQuantity -= $valuation->quantity;
            }

            $quantity = $runningQuantity;
            if ($quantity > 0 && $runningQuantity > 0) {
                // Calculate average cost from total value and quantity
                $averageCost = $runningValue->dividedBy($incomingValuations->sum('quantity'), RoundingMode::HALF_UP);
                $value = $averageCost->multipliedBy($quantity, RoundingMode::HALF_UP);
            }
        } else {
            // For FIFO/LIFO, use cost layers
            foreach ($productCostLayers as $layer) {
                if ($layer->remaining_quantity > 0) {
                    $layerValue = $layer->cost_per_unit->multipliedBy($layer->remaining_quantity, RoundingMode::HALF_UP);
                    $quantity += $layer->remaining_quantity;
                    $value = $value->plus($layerValue);

                    $layers[] = [
                        'purchase_date' => $layer->purchase_date,
                        'quantity' => $layer->remaining_quantity,
                        'cost_per_unit' => $layer->cost_per_unit,
                        'total_value' => $layerValue,
                    ];
                }
            }
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $quantity,
            'value' => $value,
            'valuation_method' => $product->inventory_valuation_method,
            'cost_layers' => $layers,
        ];
    }

    /**
     * Get inventory account balance from journal entries
     */
    private function getInventoryAccountBalance(Company $company, Carbon $asOfDate): Money
    {
        $inventoryAccountIds = Product::where('company_id', $company->id)
            ->where('type', 'storable')
            ->whereNotNull('default_inventory_account_id')
            ->pluck('default_inventory_account_id')
            ->unique();

        $lines = JournalEntryLine::query()
            ->whereIn('account_id', $inventoryAccountIds)
            ->whereHas('journalEntry', function (Builder $query) use ($asOfDate) {
                $query->where('entry_date', '<=', $asOfDate)
                    ->where('is_posted', true);
            })
            ->get();

        $totalDebit = Money::of(0, $company->currency->code);
        $totalCredit = Money::of(0, $company->currency->code);

        foreach ($lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit ?? Money::of(0, $company->currency->code));
            $totalCredit = $totalCredit->plus($line->credit ?? Money::of(0, $company->currency->code));
        }

        return $totalDebit->minus($totalCredit);
    }

    /**
     * Get default aging buckets
     */
    private function getDefaultAgingBuckets(): array
    {
        return [
            ['min' => 0, 'max' => 30, 'label' => '0-30 days'],
            ['min' => 31, 'max' => 60, 'label' => '31-60 days'],
            ['min' => 61, 'max' => 90, 'label' => '61-90 days'],
            ['min' => 91, 'max' => 180, 'label' => '91-180 days'],
            ['min' => 181, 'max' => 999999, 'label' => '180+ days'],
        ];
    }

    /**
     * Find the appropriate aging bucket for given age in days
     */
    private function findAgingBucket(int $ageInDays, array $buckets): ?array
    {
        foreach ($buckets as $bucket) {
            if ($ageInDays >= $bucket['min'] && $ageInDays <= $bucket['max']) {
                return $bucket;
            }
        }

        return null;
    }

    /**
     * Get lots expiring within warning period
     */
    private function getExpiringLots(Company $company, int $warningDays): array
    {
        $cutoffDate = now()->addDays($warningDays);

        return Lot::where('company_id', $company->id)
            ->where('active', true)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', $cutoffDate)
            ->whereHas('stockQuants', function (Builder $query) {
                $query->where('quantity', '>', 0);
            })
            ->with(['product', 'stockQuants'])
            ->get()
            ->map(function (Lot $lot) {
                return [
                    'lot_code' => $lot->lot_code,
                    'product_name' => $lot->product->name,
                    'expiration_date' => $lot->expiration_date,
                    'days_until_expiration' => $lot->daysUntilExpiration(),
                    'quantity_on_hand' => $lot->stockQuants->sum('quantity'),
                ];
            })
            ->toArray();
    }

    /**
     * Get COGS for a specific period
     */
    private function getCOGSForPeriod(Company $company, Carbon $startDate, Carbon $endDate): Money
    {
        $cogsAccountIds = Product::where('company_id', $company->id)
            ->where('type', 'storable')
            ->whereNotNull('default_cogs_account_id')
            ->pluck('default_cogs_account_id')
            ->unique();

        $cogs = JournalEntryLine::query()
            ->whereIn('account_id', $cogsAccountIds)
            ->whereHas('journalEntry', function (Builder $query) use ($startDate, $endDate) {
                $query->whereBetween('entry_date', [$startDate, $endDate])
                    ->where('is_posted', true);
            })
            ->sum('debit') ?? 0;

        return Money::of($cogs, $company->currency->code);
    }

    /**
     * Get average inventory value for a period
     */
    private function getAverageInventoryValue(Company $company, Carbon $startDate, Carbon $endDate): Money
    {
        // Sample inventory value at beginning, middle, and end of period
        $startValue = $this->valuationAt($startDate, ['company_id' => $company->id])['total_value'];
        $midDate = $startDate->copy()->addDays($startDate->diffInDays($endDate) / 2);
        $midValue = $this->valuationAt($midDate, ['company_id' => $company->id])['total_value'];
        $endValue = $this->valuationAt($endDate, ['company_id' => $company->id])['total_value'];

        $totalValue = $startValue->plus($midValue)->plus($endValue);
        $averageValue = $totalValue->dividedBy(3, RoundingMode::HALF_UP);

        return $averageValue;
    }
}
