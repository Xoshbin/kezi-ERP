<?php

namespace Modules\Foundation\Filament\Components;

use App\Enums\Inventory\StockMoveType;
use App\Services\Inventory\CostValidationService;
use Filament\Schemas\Components\View;

/**
 * Custom Filament component for displaying cost previews in stock move forms
 *
 * This component provides real-time cost validation and preview information
 * to help users understand the financial impact of their stock movements.
 */
class CostPreviewComponent
{

    /**
     * Create a cost preview view component for a product line in stock move forms
     */
    public static function forProductLine(string $productFieldName = 'product_id', string $quantityFieldName = 'quantity'): View
    {
        return View::make('filament.components.cost-preview')
            ->viewData(function ($get) use ($productFieldName, $quantityFieldName) {
                $productId = $get($productFieldName);
                $quantity = $get($quantityFieldName);
                $moveType = $get('../../move_type'); // Get move type from parent form

                if (!$productId || !$quantity || !$moveType) {
                    return [
                        'status' => 'empty',
                        'message' => __('Select product and quantity to see cost preview'),
                    ];
                }

                try {
                    $product = \Modules\Product\Models\Product::find($productId);
                    if (!$product) {
                        return [
                            'status' => 'error',
                            'message' => __('Product not found'),
                        ];
                    }

                    $costValidationService = app(CostValidationService::class);
                    $movementValidationService = app(\App\Services\Inventory\InventoryMovementValidationService::class);
                    $moveTypeEnum = StockMoveType::from($moveType);

                    // First validate the movement itself
                    $movementValidation = $movementValidationService->validateMovement(
                        $product,
                        $moveTypeEnum,
                        (float) $quantity
                    );

                    if (!$movementValidation->isValid()) {
                        $guidance = $movementValidationService->getResolutionGuidance($product);

                        return [
                            'status' => 'invalid',
                            'message' => $movementValidation->getSummary(),
                            'errors' => $movementValidation->getErrors(),
                            'requirements' => $movementValidation->getRequirements(),
                            'suggestedActions' => $guidance['contextual_suggestions'],
                            'establishmentSteps' => $guidance['establishment_steps'],
                        ];
                    }

                    // If movement is valid, get cost preview
                    $costPreview = $costValidationService->getCostPreview(
                        $product,
                        (float) $quantity,
                        $moveTypeEnum,
                        null,
                        false // Don't allow fallbacks by default
                    );

                    if ($costPreview->isValid()) {
                        $result = [
                            'status' => 'valid',
                            'unitCost' => $costPreview->getUnitCost(),
                            'totalCost' => $costPreview->getTotalCost(),
                            'costSource' => $costPreview->getCostSource(),
                            'warnings' => $costPreview->hasWarnings() ? $costPreview->getWarnings() : [],
                        ];

                        // Add movement validation warnings if any
                        if ($movementValidation->hasWarnings()) {
                            $result['warnings'] = array_merge(
                                $result['warnings'],
                                $movementValidation->getWarnings()
                            );
                        }

                        return $result;
                    } else {
                        return [
                            'status' => 'invalid',
                            'message' => $costPreview->getMessage(),
                            'suggestedActions' => $costPreview->getSuggestedActions(),
                        ];
                    }
                } catch (\Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => __('Error calculating cost preview') . ': ' . $e->getMessage(),
                    ];
                }
            });
    }

    /**
     * Create a cost validation summary for the entire stock move
     */
    public static function forStockMove(): View
    {
        return View::make('filament.components.cost-summary')
            ->viewData(function ($get) {
                $productLines = $get('productLines') ?? [];
                $moveType = $get('move_type');

                if (empty($productLines) || !$moveType) {
                    return [
                        'status' => 'empty',
                        'message' => __('Add product lines to see cost summary'),
                    ];
                }

                try {
                    $costValidationService = app(CostValidationService::class);
                    $moveTypeEnum = StockMoveType::from($moveType);
                    $totalCost = null;
                    $hasErrors = false;
                    $warnings = [];

                    foreach ($productLines as $line) {
                        if (!isset($line['product_id']) || !isset($line['quantity'])) {
                            continue;
                        }

                        $product = \Modules\Product\Models\Product::find($line['product_id']);
                        if (!$product) {
                            continue;
                        }

                        $costPreview = $costValidationService->getCostPreview(
                            $product,
                            (float) $line['quantity'],
                            $moveTypeEnum
                        );

                        if ($costPreview->isValid()) {
                            if ($totalCost === null) {
                                $totalCost = $costPreview->getTotalCost();
                            } else {
                                $totalCost = $totalCost->plus($costPreview->getTotalCost());
                            }

                            if ($costPreview->hasWarnings()) {
                                $warnings = array_merge($warnings, $costPreview->getWarnings());
                            }
                        } else {
                            $hasErrors = true;
                        }
                    }

                    return [
                        'status' => 'calculated',
                        'totalCost' => $totalCost,
                        'hasErrors' => $hasErrors,
                        'warnings' => array_unique($warnings),
                    ];
                } catch (\Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => __('Error calculating cost summary') . ': ' . $e->getMessage(),
                    ];
                }
            });
    }
}
