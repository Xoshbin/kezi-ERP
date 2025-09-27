<?php

namespace Modules\Inventory\Services\Inventory;

use App\Models\Product;
use App\Models\StockMove;
use App\Enums\Inventory\StockMoveType;
use App\Exceptions\Inventory\InsufficientCostInformationException;
use App\DataTransferObjects\Inventory\InventoryMovementValidationResult;

/**
 * Service for validating inventory movements before execution
 * 
 * Ensures all business rules and cost requirements are met before
 * allowing inventory movements to proceed.
 */
class InventoryMovementValidationService
{
    public function __construct(
        private ProductCostAnalysisService $costAnalysisService
    ) {}

    /**
     * Validate that a product is ready for inventory movements
     *
     * @param Product $product
     * @param StockMoveType $moveType
     * @param float $quantity
     * @return InventoryMovementValidationResult
     */
    public function validateMovement(
        Product $product,
        StockMoveType $moveType,
        float $quantity
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
            if (!$this->costAnalysisService->isReadyForInventoryMovements($product)) {
                $errors[] = 'Product lacks sufficient cost information for inventory movements';
                $requirements = $this->costAnalysisService->getMinimumRequirements($product);
                
                return InventoryMovementValidationResult::failed($errors, $warnings, $requirements);
            }
        }

        // 3. Validate quantity for outgoing movements
        if ($moveType === StockMoveType::Outgoing) {
            if ($product->quantity_on_hand < $quantity) {
                $errors[] = "Insufficient stock: Available {$product->quantity_on_hand}, Requested {$quantity}";
            }
            
            if (!$this->costAnalysisService->isReadyForInventoryMovements($product)) {
                $errors[] = 'Product lacks cost information required for COGS calculation';
                $requirements = $this->costAnalysisService->getMinimumRequirements($product);
            }
        }

        // 4. Validate inventory accounts
        if (!$product->default_inventory_account_id) {
            $errors[] = 'Product must have a default inventory account configured';
        }

        if (!$product->default_cogs_account_id && $moveType === StockMoveType::Outgoing) {
            $errors[] = 'Product must have a COGS account configured for outgoing movements';
        }

        if (!$product->default_stock_input_account_id && $moveType === StockMoveType::Incoming) {
            $errors[] = 'Product must have a stock input account configured for incoming movements';
        }

        // 5. Add warnings for potential issues
        $vendorBillAnalysis = $this->costAnalysisService->analyzeVendorBillStatus($product);
        if ($vendorBillAnalysis['draft_count'] > 0) {
            $warnings[] = "Product has {$vendorBillAnalysis['draft_count']} draft vendor bill(s) that could affect cost calculation";
        }

        if (!empty($errors)) {
            return InventoryMovementValidationResult::failed($errors, $warnings, $requirements);
        }

        if (!empty($warnings)) {
            return InventoryMovementValidationResult::warning($warnings);
        }

        return InventoryMovementValidationResult::success();
    }

    /**
     * Validate a complete stock move before execution
     *
     * @param StockMove $stockMove
     * @return InventoryMovementValidationResult
     */
    public function validateStockMove(StockMove $stockMove): InventoryMovementValidationResult
    {
        $allErrors = [];
        $allWarnings = [];
        $allRequirements = [];

        foreach ($stockMove->productLines as $line) {
            if (!$line->product) {
                $allErrors[] = "Product line {$line->id} has no associated product";
                continue;
            }

            $result = $this->validateMovement(
                $line->product,
                $stockMove->move_type,
                $line->quantity
            );

            if (!$result->isValid()) {
                $allErrors = array_merge($allErrors, $result->getErrors());
                $allWarnings = array_merge($allWarnings, $result->getWarnings());
                $allRequirements = array_merge($allRequirements, $result->getRequirements());
            }
        }

        if (!empty($allErrors)) {
            return InventoryMovementValidationResult::failed($allErrors, $allWarnings, $allRequirements);
        }

        if (!empty($allWarnings)) {
            return InventoryMovementValidationResult::warning($allWarnings);
        }

        return InventoryMovementValidationResult::success();
    }

    /**
     * Get detailed guidance for resolving validation failures
     *
     * @param Product $product
     * @return array
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
