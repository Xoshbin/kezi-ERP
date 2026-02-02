<?php

namespace Kezi\Inventory\Services\Inventory;

use Kezi\Inventory\DataTransferObjects\Inventory\InventoryMovementValidationResult;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockMove;
use Kezi\Product\Models\Product;

/**
 * Service for validating inventory movements before execution
 *
 * Ensures all business rules and cost requirements are met before
 * allowing inventory movements to proceed.
 */
class InventoryMovementValidationService
{
    public function __construct(
        private ProductCostAnalysisService $costAnalysisService,
        private StockQuantService $stockQuantService,
    ) {}

    /**
     * Validate that a product is ready for inventory movements
     *
     * @param  int|null  $locationId  Optional location ID for location-specific validation
     */
    public function validateMovement(
        Product $product,
        StockMoveType $moveType,
        float $quantity,
        ?int $locationId = null,
    ): InventoryMovementValidationResult {

        $errors = [];
        $warnings = [];
        $requirements = [];

        // 1. Validate product type
        if ($product->type->value !== 'storable') {
            $errors[] = 'Only storable products can have inventory movements';

            return InventoryMovementValidationResult::failed($errors, $warnings, $requirements);
        }

        // 2. Validate cost information for incoming movements
        if ($moveType === StockMoveType::Incoming) {
            if (! $this->costAnalysisService->isReadyForInventoryMovements($product)) {
                $errors[] = 'Product lacks sufficient cost information for inventory movements';
                $requirements = $this->costAnalysisService->getMinimumRequirements($product);

                return InventoryMovementValidationResult::failed($errors, $warnings, $requirements);
            }
        }

        // 3. Validate quantity for outgoing movements using StockQuant as source of truth
        if ($moveType === StockMoveType::Outgoing) {
            // Get available quantity from StockQuant (location-aware if location specified)
            $availableQuantity = $this->stockQuantService->available(
                $product->company_id,
                $product->id,
                $locationId
            );

            if ($availableQuantity < $quantity) {
                $locationInfo = $locationId ? " at location {$locationId}" : '';
                $errors[] = "Insufficient stock{$locationInfo}: Available {$availableQuantity}, Requested {$quantity}";
            }

            if (! $this->costAnalysisService->isReadyForInventoryMovements($product)) {
                $errors[] = 'Product lacks cost information required for COGS calculation';
                $requirements = $this->costAnalysisService->getMinimumRequirements($product);
            }
        }

        // 4. Validate inventory accounts
        if (! $product->default_inventory_account_id) {
            $errors[] = 'Product must have a default inventory account configured';
        }

        if (! $product->default_cogs_account_id && $moveType === StockMoveType::Outgoing) {
            $errors[] = 'Product must have a COGS account configured for outgoing movements';
        }

        if (! $product->default_stock_input_account_id && $moveType === StockMoveType::Incoming) {
            $errors[] = 'Product must have a stock input account configured for incoming movements';
        }

        // 5. Add warnings for potential issues
        $vendorBillAnalysis = $this->costAnalysisService->analyzeVendorBillStatus($product);
        if ($vendorBillAnalysis['draft_count'] > 0) {
            $warnings[] = "Product has {$vendorBillAnalysis['draft_count']} draft vendor bill(s) that could affect cost calculation";
        }

        if (! empty($errors)) {
            return InventoryMovementValidationResult::failed($errors, $warnings, $requirements);
        }

        if (! empty($warnings)) {
            return InventoryMovementValidationResult::warning($warnings);
        }

        return InventoryMovementValidationResult::success();
    }

    /**
     * Validate a complete stock move before execution
     */
    public function validateStockMove(StockMove $stockMove): InventoryMovementValidationResult
    {
        $allErrors = [];
        $allWarnings = [];
        $allRequirements = [];

        foreach ($stockMove->productLines as $line) {
            if (! $line->product) {
                $allErrors[] = "Product line {$line->id} has no associated product";

                continue;
            }

            $result = $this->validateMovement(
                $line->product,
                $stockMove->move_type,
                $line->quantity
            );

            if (! $result->isValid()) {
                $allErrors = array_merge($allErrors, $result->getErrors());
                $allWarnings = array_merge($allWarnings, $result->getWarnings());
                $allRequirements = array_merge($allRequirements, $result->getRequirements());
            }
        }

        if (! empty($allErrors)) {
            return InventoryMovementValidationResult::failed($allErrors, $allWarnings, $allRequirements);
        }

        if (! empty($allWarnings)) {
            return InventoryMovementValidationResult::warning($allWarnings);
        }

        return InventoryMovementValidationResult::success();
    }

    /**
     * Get detailed guidance for resolving validation failures
     */
    public function getResolutionGuidance(Product $product): array
    {
        return [
            'establishment_steps' => $this->costAnalysisService->getEstablishmentSteps($product),
            'minimum_requirements' => $this->costAnalysisService->getMinimumRequirements($product),
            'contextual_suggestions' => $this->costAnalysisService->getContextualCostSuggestions($product),
        ];
    }
}
