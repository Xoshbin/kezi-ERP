<?php

namespace Modules\Inventory\Services\Inventory;

use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBillLine;

/**
 * Service for analyzing product cost information and providing contextual guidance
 *
 * This service implements robust cost analysis following industry best practices:
 *
 * 1. **No Fallback to Unreliable Data**: Unlike systems that use sales prices as cost,
 *    this service requires proper purchase cost establishment through vendor bills.
 *
 * 2. **Context-Aware Guidance**: Provides specific, actionable steps based on the
 *    actual state of vendor bills and product configuration.
 *
 * 3. **Valuation Method Specific**: Handles AVCO, FIFO, and LIFO methods with
 *    appropriate cost establishment requirements.
 *
 * 4. **Comprehensive Validation**: Ensures all business rules are met before
 *    allowing inventory movements to proceed.
 *
 * Best Practices Implemented:
 * - Cost information must come from actual purchase transactions
 * - Proper vendor bill workflow enforcement
 * - Clear establishment steps for missing cost data
 * - Minimum requirements documentation
 * - No emergency fallbacks to unreliable data sources
 */
class ProductCostAnalysisService
{
    /**
     * Analyze vendor bill status for a product
     */
    public function analyzeVendorBillStatus(Product $product): array
    {
        $vendorBillLines = VendorBillLine::where('product_id', $product->id)
            ->with(['vendorBill' => function ($query) {
                $query->select('id', 'status', 'posted_at', 'bill_reference');
            }])
            ->get();

        $draftCount = 0;
        $postedCount = 0;
        $latestPostedBill = null;

        foreach ($vendorBillLines as $line) {
            if ($line->vendorBill) {
                if ($line->vendorBill->status === VendorBillStatus::Draft) {
                    $draftCount++;
                } elseif ($line->vendorBill->status === VendorBillStatus::Posted) {
                    $postedCount++;
                    if (! $latestPostedBill || $line->vendorBill->posted_at > $latestPostedBill->posted_at) {
                        $latestPostedBill = $line->vendorBill;
                    }
                }
            }
        }

        return [
            'has_vendor_bills' => $vendorBillLines->isNotEmpty(),
            'draft_count' => $draftCount,
            'posted_count' => $postedCount,
            'latest_posted_bill' => $latestPostedBill,
            'total_lines' => $vendorBillLines->count(),
        ];
    }

    /**
     * Get context-aware cost suggestions for a product
     */
    public function getContextualCostSuggestions(Product $product): array
    {
        $suggestions = [];
        $vendorBillAnalysis = $this->analyzeVendorBillStatus($product);

        // Analyze current cost state
        $hasAverageCost = $product->average_cost && $product->average_cost->isPositive();
        $hasCostLayers = $product->inventoryCostLayers()->where('remaining_quantity', '>', 0)->exists();

        // Vendor bill related suggestions
        if (! $vendorBillAnalysis['has_vendor_bills']) {
            $suggestions[] = 'Create and post a vendor bill for this product to establish purchase cost';
        } elseif ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            $suggestions[] = "Post the {$vendorBillAnalysis['draft_count']} draft vendor bill(s) for this product to establish cost";
        } elseif ($vendorBillAnalysis['posted_count'] > 0) {
            if ($product->inventory_valuation_method === ValuationMethod::AVCO && ! $hasAverageCost) {
                $suggestions[] = 'Check why posted vendor bills are not updating the average cost - verify product configuration and bill posting process';
            } elseif ($product->inventory_valuation_method !== ValuationMethod::AVCO && ! $hasCostLayers) {
                $suggestions[] = 'Check why posted vendor bills are not creating cost layers - verify inventory accounting configuration';
            }
        }

        // Valuation method specific suggestions
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            if (! $hasAverageCost && $vendorBillAnalysis['posted_count'] === 0) {
                $suggestions[] = 'Average cost is calculated automatically from posted vendor bills - no manual entry needed';
            }
        } else {
            // FIFO/LIFO methods
            if (! $hasCostLayers) {
                $suggestions[] = 'Cost layers (historical purchase costs) are created when vendor bills with storable products are posted';
            }
        }

        // Proper cost establishment guidance (no fallbacks to unreliable data)
        if (! $this->isReadyForInventoryMovements($product)) {
            $suggestions[] = 'Cost information is required before processing inventory movements';

            // Add specific establishment steps
            $establishmentSteps = $this->getEstablishmentSteps($product);
            $suggestions = array_merge($suggestions, $establishmentSteps);

            // Add minimum requirements reference
            $suggestions[] = 'Review minimum requirements for cost establishment in product documentation';
        }

        return $suggestions;
    }

    /**
     * Get a detailed cost status explanation for a product
     */
    public function getCostStatusExplanation(Product $product): string
    {
        $vendorBillAnalysis = $this->analyzeVendorBillStatus($product);

        $explanation = "Cannot determine cost for product '{$product->name}' (ID: {$product->id}). ";

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $explanation .= 'Using AVCO valuation method - requires positive average cost. ';

            if ($vendorBillAnalysis['posted_count'] > 0) {
                $explanation .= "Found {$vendorBillAnalysis['posted_count']} posted vendor bill(s) but average cost is not set. ";
            } elseif ($vendorBillAnalysis['draft_count'] > 0) {
                $explanation .= "Found {$vendorBillAnalysis['draft_count']} draft vendor bill(s) - these need to be posted to calculate average cost. ";
            } else {
                $explanation .= 'No vendor bills found for this product. ';
            }
        } else {
            $explanation .= "Using {$product->inventory_valuation_method->label()} valuation method - requires cost layers. ";

            if ($vendorBillAnalysis['posted_count'] > 0) {
                $explanation .= "Found {$vendorBillAnalysis['posted_count']} posted vendor bill(s) but no cost layers available. ";
            } else {
                $explanation .= 'No posted vendor bills found to create cost layers. ';
            }
        }

        return $explanation;
    }

    /**
     * Get specific action steps for establishing proper cost information
     */
    public function getEstablishmentSteps(Product $product): array
    {
        $steps = [];
        $vendorBillAnalysis = $this->analyzeVendorBillStatus($product);

        if (! $vendorBillAnalysis['has_vendor_bills']) {
            $steps[] = '1. Obtain purchase invoices from your supplier for this product';
            $steps[] = '2. Create a vendor bill in the system using the purchase invoice data';
            $steps[] = '3. Ensure the vendor bill includes this product with correct quantities and unit prices';
            $steps[] = '4. Post the vendor bill to establish cost information';
        } elseif ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            $steps[] = '1. Review the draft vendor bill(s) for accuracy';
            $steps[] = '2. Verify product quantities and unit prices are correct';
            $steps[] = '3. Post the vendor bill(s) to establish cost information';
        } elseif ($vendorBillAnalysis['posted_count'] > 0) {
            $steps[] = '1. Verify the posted vendor bills contain this product';
            $steps[] = '2. Check that inventory accounting is properly configured';
            $steps[] = '3. Ensure the product has proper inventory accounts assigned';
            $steps[] = '4. Contact system administrator if cost calculation is not working';
        }

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $steps[] = 'Note: AVCO method automatically calculates average cost from all posted vendor bills';
        } else {
            $steps[] = 'Note: FIFO/LIFO methods create individual cost layers for each purchase transaction';
        }

        return $steps;
    }

    /**
     * Check if a product is ready for inventory movements
     */
    public function isReadyForInventoryMovements(Product $product): bool
    {
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            return $product->average_cost && $product->average_cost->isPositive();
        }

        return $product->inventoryCostLayers()->where('remaining_quantity', '>', 0)->exists();
    }

    /**
     * Get the minimum requirements for cost establishment
     */
    public function getMinimumRequirements(Product $product): array
    {
        $requirements = [
            'product_type' => 'Product must be of type "Storable"',
            'vendor_bill' => 'At least one posted vendor bill containing this product',
            'unit_price' => 'Vendor bill must have positive unit price for the product',
            'inventory_accounts' => 'Product must have inventory accounts configured',
        ];

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $requirements['valuation_method'] = 'AVCO method requires average cost calculation from vendor bills';
        } else {
            $requirements['valuation_method'] = 'FIFO/LIFO methods require cost layer creation from vendor bills';
        }

        return $requirements;
    }
}
