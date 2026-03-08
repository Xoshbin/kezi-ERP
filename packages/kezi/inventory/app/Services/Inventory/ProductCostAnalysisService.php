<?php

namespace Kezi\Inventory\Services\Inventory;

use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBillLine;

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
            $suggestions[] = __('inventory::exceptions.cost_analysis.create_bill');
        } elseif ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            $suggestions[] = __('inventory::exceptions.cost_analysis.post_draft_bills', ['count' => $vendorBillAnalysis['draft_count']]);
        } elseif ($vendorBillAnalysis['posted_count'] > 0) {
            if ($product->inventory_valuation_method === ValuationMethod::Avco && ! $hasAverageCost) {
                $suggestions[] = __('inventory::exceptions.cost_analysis.check_avco_mismatch');
            } elseif ($product->inventory_valuation_method !== ValuationMethod::Avco && ! $hasCostLayers) {
                $suggestions[] = __('inventory::exceptions.cost_analysis.check_layers_mismatch');
            }
        }

        // Valuation method specific suggestions
        if ($product->inventory_valuation_method === ValuationMethod::Avco) {
            if (! $hasAverageCost && $vendorBillAnalysis['posted_count'] === 0) {
                $suggestions[] = __('inventory::exceptions.cost_analysis.avco_auto_calc');
            }
        } else {
            // FIFO/LIFO methods
            if (! $hasCostLayers) {
                $suggestions[] = __('inventory::exceptions.cost_analysis.layers_auto_create');
            }
        }

        // Proper cost establishment guidance (no fallbacks to unreliable data)
        if (! $this->isReadyForInventoryMovements($product)) {
            $suggestions[] = __('inventory::exceptions.cost_analysis.cost_info_required');

            // Add specific establishment steps
            $establishmentSteps = $this->getEstablishmentSteps($product);
            $suggestions = array_merge($suggestions, $establishmentSteps);

            // Add minimum requirements reference
            $suggestions[] = __('inventory::exceptions.cost_analysis.review_requirements');
        }

        return $suggestions;
    }

    /**
     * Get a detailed cost status explanation for a product
     */
    public function getCostStatusExplanation(Product $product): string
    {
        $vendorBillAnalysis = $this->analyzeVendorBillStatus($product);

        $explanation = __('inventory::exceptions.cost_analysis.explanation.main', ['product_name' => $product->name, 'product_id' => $product->id]);

        if ($product->inventory_valuation_method === ValuationMethod::Avco) {
            $explanation .= __('inventory::exceptions.cost_analysis.explanation.avco');

            if ($vendorBillAnalysis['posted_count'] > 0) {
                $explanation .= __('inventory::exceptions.cost_analysis.explanation.posted_no_cost', ['count' => $vendorBillAnalysis['posted_count']]);
            } elseif ($vendorBillAnalysis['draft_count'] > 0) {
                $explanation .= __('inventory::exceptions.cost_analysis.explanation.draft_no_cost', ['count' => $vendorBillAnalysis['draft_count']]);
            } else {
                $explanation .= __('inventory::exceptions.cost_analysis.explanation.no_bills');
            }
        } else {
            $explanation .= __('inventory::exceptions.cost_analysis.explanation.fifo_lifo', ['method' => $product->inventory_valuation_method->label()]);

            if ($vendorBillAnalysis['posted_count'] > 0) {
                $explanation .= __('inventory::exceptions.cost_analysis.explanation.posted_no_layers', ['count' => $vendorBillAnalysis['posted_count']]);
            } else {
                $explanation .= __('inventory::exceptions.cost_analysis.explanation.no_bills');
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
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.obtain_invoices');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.create_bill');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.ensure_correct_price');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.post_bill');
        } elseif ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.review_drafts');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.verify_quantities');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.post_reviewed_bills');
        } elseif ($vendorBillAnalysis['posted_count'] > 0) {
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.verify_posted_product');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.check_accounting_config');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.ensure_accounts_assigned');
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.contact_admin');
        }

        if ($product->inventory_valuation_method === ValuationMethod::Avco) {
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.avco_note');
        } else {
            $steps[] = __('inventory::exceptions.cost_analysis.establishment.fifo_lifo_note');
        }

        return $steps;
    }

    /**
     * Check if a product is ready for inventory movements
     */
    public function isReadyForInventoryMovements(Product $product): bool
    {
        if ($product->inventory_valuation_method === ValuationMethod::Avco) {
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
            'product_type' => __('inventory::exceptions.cost_analysis.requirements.product_type'),
            'vendor_bill' => __('inventory::exceptions.cost_analysis.requirements.vendor_bill'),
            'unit_price' => __('inventory::exceptions.cost_analysis.requirements.unit_price'),
            'inventory_accounts' => __('inventory::exceptions.cost_analysis.requirements.inventory_accounts'),
        ];

        if ($product->inventory_valuation_method === ValuationMethod::Avco) {
            $requirements['valuation_method'] = __('inventory::exceptions.cost_analysis.requirements.avco_method');
        } else {
            $requirements['valuation_method'] = __('inventory::exceptions.cost_analysis.requirements.fifo_lifo_method');
        }

        return $requirements;
    }
}
