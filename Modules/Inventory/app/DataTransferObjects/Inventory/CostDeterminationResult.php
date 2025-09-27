<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;

use App\Enums\Inventory\CostSource;
use Brick\Money\Money;

/**
 * Data Transfer Object for cost determination results
 * 
 * Contains the determined cost along with metadata about how it was calculated,
 * providing transparency and audit trail for inventory valuation operations.
 */
readonly class CostDeterminationResult
{
    public function __construct(
        public Money $cost,
        public CostSource $source,
        public string $reference = '',
        public array $warnings = [],
        public array $attemptedSources = []
    ) {}
    
    /**
     * Check if the cost determination has any warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
    
    /**
     * Check if the cost source is considered reliable
     */
    public function isReliable(): bool
    {
        return $this->source->isReliable();
    }
    
    /**
     * Check if the cost determination requires user attention
     */
    public function requiresWarning(): bool
    {
        return $this->source->requiresWarning() || $this->hasWarnings();
    }
    
    /**
     * Get a formatted description of the cost determination
     */
    public function getDescription(): string
    {
        $description = "Cost {$this->cost->getAmount()} from {$this->source->label()}";
        
        if (!empty($this->reference)) {
            $description .= " ({$this->reference})";
        }
        
        return $description;
    }
    
    /**
     * Get all warnings as a formatted string
     */
    public function getWarningsText(): string
    {
        return implode('; ', $this->warnings);
    }
    
    /**
     * Create a successful result
     */
    public static function success(
        Money $cost,
        CostSource $source,
        string $reference = '',
        array $warnings = [],
        array $attemptedSources = []
    ): self {
        return new self($cost, $source, $reference, $warnings, $attemptedSources);
    }
    
    /**
     * Create a result with warnings
     */
    public static function withWarnings(
        Money $cost,
        CostSource $source,
        array $warnings,
        string $reference = '',
        array $attemptedSources = []
    ): self {
        return new self($cost, $source, $reference, $warnings, $attemptedSources);
    }
}
