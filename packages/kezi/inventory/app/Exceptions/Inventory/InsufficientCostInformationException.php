<?php

namespace Kezi\Inventory\Exceptions\Inventory;

use Exception;
use Kezi\Inventory\Services\Inventory\ProductCostAnalysisService;
use Kezi\Inventory\Services\Inventory\UserFriendlyErrorService;
use Kezi\Product\Models\Product;

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
        ?string $message = null,
    ) {
        // Use the new analysis service for better error messages
        $analysisService = app(ProductCostAnalysisService::class);
        $defaultMessage = $analysisService->getCostStatusExplanation($product);

        if (! empty($this->attemptedSources)) {
            $defaultMessage .= ' Attempted sources: '.implode(', ', $this->attemptedSources).'.';
        }

        if (! empty($this->suggestedActions)) {
            $defaultMessage .= ' Suggested actions: '.implode(', ', $this->suggestedActions).'.';
        }

        parent::__construct($message ?? $defaultMessage);
    }

    /**
     * Get suggested actions for resolving the cost information issue
     */
    public function getSuggestedActions(): array
    {
        if (! empty($this->suggestedActions)) {
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

    /**
     * Get user-friendly error message for notifications
     */
    public function getUserFriendlyMessage(): string
    {
        $errorService = app(UserFriendlyErrorService::class);

        return $errorService->getNotificationMessage($this);
    }

    /**
     * Get detailed user-friendly error information
     */
    public function getUserFriendlyDetails(): array
    {
        $errorService = app(UserFriendlyErrorService::class);

        return $errorService->getDetailedErrorInfo($this);
    }

    /**
     * Get converted user-friendly error data
     */
    public function getUserFriendlyErrorData(): array
    {
        $errorService = app(UserFriendlyErrorService::class);

        return $errorService->convertCostInformationException($this);
    }
}
