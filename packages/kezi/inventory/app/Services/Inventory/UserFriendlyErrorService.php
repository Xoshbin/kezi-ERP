<?php

namespace Kezi\Inventory\Services\Inventory;

use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Product\Models\Product;

/**
 * Service for converting technical inventory exceptions into user-friendly error messages
 *
 * This service transforms technical error messages and exception details into
 * clear, actionable guidance that warehouse and inventory users can understand
 * and act upon. It avoids technical jargon and focuses on business processes.
 */
class UserFriendlyErrorService
{
    public function __construct(
        protected ProductCostAnalysisService $costAnalysisService,
    ) {}

    /**
     * Convert InsufficientCostInformationException to user-friendly error data
     *
     * @return array Array containing title, message, explanation, solutions, and next_steps
     */
    public function convertCostInformationException(InsufficientCostInformationException $exception): array
    {
        $product = $exception->getProduct();
        $vendorBillAnalysis = $this->costAnalysisService->analyzeVendorBillStatus($product);

        return [
            'title' => __('inventory::inventory_accounting.cost_validation_errors.title'),
            'message' => __('inventory::inventory_accounting.cost_validation_errors.message', [
                'product_name' => $product->name,
            ]),
            'explanation' => $this->getValuationMethodExplanation($product),
            'primary_solution' => $this->getPrimarySolution($product, $vendorBillAnalysis),
            'next_steps' => $this->getNextSteps($product, $vendorBillAnalysis),
            'help_text' => __('inventory::inventory_accounting.cost_validation_errors.help_text'),
        ];
    }

    /**
     * Get explanation based on product's valuation method
     */
    protected function getValuationMethodExplanation(Product $product): string
    {
        $method = strtolower($product->inventory_valuation_method->value);

        return __("inventory::inventory_accounting.cost_validation_errors.explanation.{$method}");
    }

    /**
     * Get the primary solution based on vendor bill analysis
     */
    protected function getPrimarySolution(Product $product, array $vendorBillAnalysis): string
    {
        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return __('inventory::inventory_accounting.cost_validation_errors.solutions.no_bills');
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return __('inventory::inventory_accounting.cost_validation_errors.solutions.draft_bills');
        }

        if ($vendorBillAnalysis['posted_count'] > 0) {
            return __('inventory::inventory_accounting.cost_validation_errors.solutions.posted_bills_no_cost');
        }

        return __('inventory::inventory_accounting.cost_validation_errors.solutions.system_issue');
    }

    /**
     * Get step-by-step next actions
     */
    protected function getNextSteps(Product $product, array $vendorBillAnalysis): array
    {
        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return [
                __('inventory::inventory_accounting.cost_validation_errors.next_steps.create_bill'),
                __('inventory::inventory_accounting.cost_validation_errors.next_steps.add_product'),
                __('inventory::inventory_accounting.cost_validation_errors.next_steps.confirm_bill'),
                __('inventory::inventory_accounting.cost_validation_errors.next_steps.retry_movement'),
            ];
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return [
                __('Go to Vendor Bills and review the draft bills'),
                __('Verify the product quantities and prices are correct'),
                __('Confirm the vendor bills to establish costs'),
                __('Return here to process the inventory movement'),
            ];
        }

        if ($vendorBillAnalysis['posted_count'] > 0) {
            return [
                __('Verify the confirmed vendor bills include this product'),
                __('Check that unit prices are greater than zero'),
                __('Contact your system administrator if the issue persists'),
            ];
        }

        return [
            __('Contact your system administrator for assistance'),
            __('Provide them with the product name and this error message'),
        ];
    }

    /**
     * Generate a concise error message for notifications
     */
    public function getNotificationMessage(InsufficientCostInformationException $exception): string
    {
        $product = $exception->getProduct();
        $vendorBillAnalysis = $this->costAnalysisService->analyzeVendorBillStatus($product);

        $baseMessage = __('Cannot process inventory movement for ":product_name".', [
            'product_name' => $product->name,
        ]);

        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return $baseMessage.' '.__('Please create and confirm a vendor bill for this product first.');
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return $baseMessage.' '.__('Please confirm the existing draft vendor bills for this product.');
        }

        return $baseMessage.' '.__('Cost information is not available. Please check vendor bills or contact support.');
    }

    /**
     * Generate detailed error information for modal dialogs
     */
    public function getDetailedErrorInfo(InsufficientCostInformationException $exception): array
    {
        $errorData = $this->convertCostInformationException($exception);
        $product = $exception->getProduct();

        return [
            'title' => $errorData['title'],
            'product_name' => $product->name,
            'product_code' => $product->code ?? $product->id,
            'valuation_method' => $product->inventory_valuation_method->label(),
            'explanation' => $errorData['explanation'],
            'solution' => $errorData['primary_solution'],
            'steps' => $errorData['next_steps'],
            'help_text' => $errorData['help_text'],
        ];
    }
}
