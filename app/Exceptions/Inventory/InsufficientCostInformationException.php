<?php

namespace App\Exceptions\Inventory;

use App\Models\Product;
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
        string $message = null
    ) {
        $defaultMessage = "Cannot determine cost per unit for product '{$product->name}' (ID: {$product->id}).";
        
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
        
        // Default suggestions based on product configuration
        $suggestions = [];
        
        if ($this->product->type->value === 'storable') {
            $suggestions[] = 'Post a vendor bill for this product to establish average cost';
            $suggestions[] = 'Set a positive average cost on the product manually';
            
            if ($this->product->inventory_valuation_method->value !== 'avco') {
                $suggestions[] = 'Create a cost layer by receiving stock from a vendor bill';
            }
            
            if ($this->product->unit_price && $this->product->unit_price->isPositive()) {
                $suggestions[] = 'Enable unit price fallback in company settings';
            }
        }
        
        return $suggestions;
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
