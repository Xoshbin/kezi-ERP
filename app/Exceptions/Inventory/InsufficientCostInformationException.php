<?php

namespace App\Exceptions\Inventory;

use App\Models\Product;
use App\Services\Inventory\ProductCostAnalysisService;
use Exception;

/**
 * Exception thrown when insufficient cost information is available for inventory operations
 *
 * This exception provides detailed context about why cost determination failed
 * and suggests actionable steps for resolution.
 */
class InsufficientCostInformationException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly array $suggestedActions = [],
        public readonly array $attemptedSources = [],
        ?string $message = null
    ) {
        // Use the new analysis service for better error messages
        $analysisService = app(ProductCostAnalysisService::class);
        $defaultMessage = $analysisService->getCostStatusExplanation($product);

        if (!empty($this->attemptedSources)) {
            $defaultMessage .= " Attempted sources: " . implode(', ', $this->attemptedSources) . ".";
        }

        if (!empty($this->suggestedActions)) {
            $defaultMessage .= " Suggested actions: " . implode(', ', $this->suggestedActions) . ".";
        }

        parent::__construct($message ?? $defaultMessage);
    }

    /**
     * Get suggested actions for resolving the cost information issue
     */
    public function getSuggestedActions(): array
    {
        if (!empty($this->suggestedActions)) {
            return $this->suggestedActions;
        }

        // Use the new analysis service for context-aware suggestions
        $analysisService = app(ProductCostAnalysisService::class);
        return $analysisService->getContextualCostSuggestions($this->product);
    }

    /**
     * Get attempted cost sources that failed
     */
    public function getAttemptedSources(): array
    {
        return $this->attemptedSources;
    }

    /**
     * Get the product that caused the exception
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}
