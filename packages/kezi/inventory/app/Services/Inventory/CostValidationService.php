<?php

namespace Kezi\Inventory\Services\Inventory;

use Kezi\Inventory\DataTransferObjects\Inventory\CostPreviewResult;
use Kezi\Inventory\DataTransferObjects\Inventory\CostValidationResult;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;

/**
 * Service for validating cost availability and providing cost previews
 *
 * This service helps prevent cost determination failures by providing
 * proactive validation and user-friendly guidance before stock moves are processed.
 */
class CostValidationService
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService,
    ) {}

    /**
     * Validate if cost can be determined for a product and stock move
     */
    public function validateCostAvailability(
        Product $product,
        StockMoveType $moveType,
        ?StockMove $stockMove = null,
        bool $allowFallbacks = false,
    ): CostValidationResult {

        // Only validate for incoming moves (outgoing moves use different logic)
        if ($moveType !== StockMoveType::Incoming) {
            return CostValidationResult::success(__('inventory::exceptions.cost_validation_errors.validation_not_required'));
        }

        // Create a temporary stock move for validation if none provided
        if (! $stockMove) {
            $stockMove = new StockMove([
                'move_type' => $moveType,
                'source_type' => null,
                'source_id' => null,
            ]);
        }

        try {
            $costResult = $this->inventoryValuationService->calculateIncomingCostPerUnitEnhanced(
                $product,
                $stockMove,
                $allowFallbacks
            );

            return CostValidationResult::success(
                __('inventory::exceptions.cost_validation_errors.cost_available'),
                $costResult
            );
        } catch (InsufficientCostInformationException $e) {
            return CostValidationResult::failure(
                $e->getMessage(),
                $e->getSuggestedActions(),
                $e->getAttemptedSources()
            );
        }
    }

    /**
     * Get cost preview for a product and quantity
     */
    public function getCostPreview(
        Product $product,
        float $quantity,
        StockMoveType $moveType,
        ?StockMove $stockMove = null,
        bool $allowFallbacks = false,
    ): CostPreviewResult {

        $validation = $this->validateCostAvailability($product, $moveType, $stockMove, $allowFallbacks);

        if (! $validation->isValid()) {
            return CostPreviewResult::invalid(
                $validation->getMessage(),
                $validation->getSuggestedActions()
            );
        }

        $costResult = $validation->getCostResult();
        if (! $costResult) {
            return CostPreviewResult::invalid(__('inventory::exceptions.cost_validation_errors.no_result'));
        }

        $totalCost = $costResult->cost->multipliedBy($quantity);

        return CostPreviewResult::valid(
            $costResult->cost,
            $totalCost,
            $costResult->source,
            $costResult->reference,
            $costResult->warnings
        );
    }

    /**
     * Check if a product has any cost information available
     */
    public function hasAnyCostInformation(Product $product): bool
    {
        // Check average cost
        if ($product->average_cost && $product->average_cost->isPositive()) {
            return true;
        }

        // Check cost layers
        if ($product->inventoryCostLayers()->where('remaining_quantity', '>', 0)->exists()) {
            return true;
        }

        // Check unit price
        if ($product->unit_price && $product->unit_price->isPositive()) {
            return true;
        }

        return false;
    }

    /**
     * Get suggested actions for improving cost availability
     */
    public function getSuggestedActions(Product $product): array
    {
        // Use the new analysis service for context-aware suggestions
        $analysisService = app(\Kezi\Inventory\Services\Inventory\ProductCostAnalysisService::class);

        return $analysisService->getContextualCostSuggestions($product);
    }
}
