<?php

namespace App\Services\Inventory;

use App\DataTransferObjects\Inventory\CostDeterminationResult;
use App\DataTransferObjects\Inventory\CostPreviewResult;
use App\DataTransferObjects\Inventory\CostValidationResult;
use App\Enums\Inventory\StockMoveType;
use App\Exceptions\Inventory\InsufficientCostInformationException;
use App\Models\Product;
use App\Models\StockMove;

/**
 * Service for validating cost availability and providing cost previews
 *
 * This service helps prevent cost determination failures by providing
 * proactive validation and user-friendly guidance before stock moves are processed.
 */
class CostValidationService
{
    public function __construct(
        protected InventoryValuationService $inventoryValuationService
    ) {}

    /**
     * Validate if cost can be determined for a product and stock move
     *
     * @param Product $product
     * @param StockMoveType $moveType
     * @param StockMove|null $stockMove
     * @param bool $allowFallbacks
     * @return CostValidationResult
     */
    public function validateCostAvailability(
        Product $product,
        StockMoveType $moveType,
        ?StockMove $stockMove = null,
        bool $allowFallbacks = false
    ): CostValidationResult {

        // Only validate for incoming moves (outgoing moves use different logic)
        if ($moveType !== StockMoveType::Incoming) {
            return CostValidationResult::success('Cost validation not required for this move type');
        }

        // Create a temporary stock move for validation if none provided
        if (!$stockMove) {
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
                'Cost can be determined',
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
     *
     * @param Product $product
     * @param float $quantity
     * @param StockMoveType $moveType
     * @param StockMove|null $stockMove
     * @param bool $allowFallbacks
     * @return CostPreviewResult
     */
    public function getCostPreview(
        Product $product,
        float $quantity,
        StockMoveType $moveType,
        ?StockMove $stockMove = null,
        bool $allowFallbacks = false
    ): CostPreviewResult {

        $validation = $this->validateCostAvailability($product, $moveType, $stockMove, $allowFallbacks);

        if (!$validation->isValid()) {
            return CostPreviewResult::invalid(
                $validation->getMessage(),
                $validation->getSuggestedActions()
            );
        }

        $costResult = $validation->getCostResult();
        if (!$costResult) {
            return CostPreviewResult::invalid('No cost result available');
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
     *
     * @param Product $product
     * @return bool
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
     *
     * @param Product $product
     * @return array
     */
    public function getSuggestedActions(Product $product): array
    {
        // Use the new analysis service for context-aware suggestions
        $analysisService = app(\App\Services\Inventory\ProductCostAnalysisService::class);
        return $analysisService->getContextualCostSuggestions($product);
    }
}
