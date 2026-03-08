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
            'title' => __('inventory::exceptions.cost_validation_errors.title'),
            'message' => __('inventory::exceptions.cost_validation_errors.message', [
                'product_name' => $product->name,
            ]),
            'explanation' => $this->getValuationMethodExplanation($product),
            'primary_solution' => $this->getPrimarySolution($product, $vendorBillAnalysis),
            'next_steps' => $this->getNextSteps($product, $vendorBillAnalysis),
            'help_text' => __('inventory::exceptions.cost_validation_errors.help_text'),
        ];
    }

    /**
     * Get explanation based on product's valuation method
     */
    protected function getValuationMethodExplanation(Product $product): string
    {
        $method = strtolower($product->inventory_valuation_method->value);

        return __("inventory::exceptions.cost_validation_errors.explanation.{$method}");
    }

    /**
     * Get the primary solution based on vendor bill analysis
     */
    protected function getPrimarySolution(Product $product, array $vendorBillAnalysis): string
    {
        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return __('inventory::exceptions.cost_validation_errors.solutions.no_bills');
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return __('inventory::exceptions.cost_validation_errors.solutions.draft_bills');
        }

        if ($vendorBillAnalysis['posted_count'] > 0) {
            return __('inventory::exceptions.cost_validation_errors.solutions.posted_bills_no_cost');
        }

        return __('inventory::exceptions.cost_validation_errors.solutions.system_issue');
    }

    /**
     * Get step-by-step next actions
     */
    protected function getNextSteps(Product $product, array $vendorBillAnalysis): array
    {
        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return [
                __('inventory::exceptions.cost_validation_errors.next_steps.create_bill'),
                __('inventory::exceptions.cost_validation_errors.next_steps.add_product'),
                __('inventory::exceptions.cost_validation_errors.next_steps.confirm_bill'),
                __('inventory::exceptions.cost_validation_errors.next_steps.retry_movement'),
            ];
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return [
                __('inventory::exceptions.cost_validation_errors.next_steps.review_drafts'),
                __('inventory::exceptions.cost_validation_errors.next_steps.verify_quantities_prices'),
                __('inventory::exceptions.cost_validation_errors.next_steps.confirm_to_establish'),
                __('inventory::exceptions.cost_validation_errors.next_steps.return_to_process'),
            ];
        }

        if ($vendorBillAnalysis['posted_count'] > 0) {
            return [
                __('inventory::exceptions.cost_validation_errors.next_steps.verify_confirmed_includes'),
                __('inventory::exceptions.cost_validation_errors.next_steps.check_unit_prices'),
                __('inventory::exceptions.cost_validation_errors.next_steps.contact_admin_if_persists'),
            ];
        }

        return [
            __('inventory::exceptions.cost_validation_errors.next_steps.contact_admin_for_assistance'),
            __('inventory::exceptions.cost_validation_errors.next_steps.provide_product_message'),
        ];
    }

    /**
     * Generate a concise error message for notifications
     */
    public function getNotificationMessage(InsufficientCostInformationException $exception): string
    {
        $product = $exception->getProduct();
        $vendorBillAnalysis = $this->costAnalysisService->analyzeVendorBillStatus($product);

        $baseMessage = __('inventory::exceptions.cost_validation_errors.notifications.base_message', [
            'product_name' => $product->name,
        ]);

        if (! $vendorBillAnalysis['has_vendor_bills']) {
            return $baseMessage.' '.__('inventory::exceptions.cost_validation_errors.notifications.create_confirm_bill');
        }

        if ($vendorBillAnalysis['draft_count'] > 0 && $vendorBillAnalysis['posted_count'] === 0) {
            return $baseMessage.' '.__('inventory::exceptions.cost_validation_errors.notifications.confirm_existing_draft');
        }

        return $baseMessage.' '.__('inventory::exceptions.cost_validation_errors.notifications.cost_not_available');
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
