<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

use Brick\Money\Money;
use Kezi\Inventory\Enums\Inventory\CostSource;

/**
 * Data Transfer Object for cost preview results
 *
 * Contains cost preview information for stock moves,
 * including unit cost, total cost, and source information.
 */
readonly class CostPreviewResult
{
    public function __construct(
        public bool $isValid,
        public string $message,
        public ?Money $unitCost = null,
        public ?Money $totalCost = null,
        public ?CostSource $costSource = null,
        public string $costSourceReference = '',
        public array $warnings = [],
        public array $suggestedActions = [],
    ) {}

    /**
     * Check if cost preview is valid
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get the preview message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the unit cost if available
     */
    public function getUnitCost(): ?Money
    {
        return $this->unitCost;
    }

    /**
     * Get the total cost if available
     */
    public function getTotalCost(): ?Money
    {
        return $this->totalCost;
    }

    /**
     * Get the cost source if available
     */
    public function getCostSource(): ?CostSource
    {
        return $this->costSource;
    }

    /**
     * Get the cost source reference
     */
    public function getCostSourceReference(): string
    {
        return $this->costSourceReference;
    }

    /**
     * Get any warnings about the cost determination
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Get suggested actions for invalid previews
     */
    public function getSuggestedActions(): array
    {
        return $this->suggestedActions;
    }

    /**
     * Get a formatted description of the cost preview
     */
    public function getDescription(): string
    {
        if (! $this->isValid) {
            return $this->message;
        }

        $description = "Unit cost: {$this->unitCost->getAmount()}";

        if ($this->costSource) {
            $description .= " from {$this->costSource->label()}";
        }

        if (! empty($this->costSourceReference)) {
            $description .= " ({$this->costSourceReference})";
        }

        if ($this->hasWarnings()) {
            $description .= ' - Warnings: '.implode(', ', $this->warnings);
        }

        return $description;
    }

    /**
     * Create a valid cost preview result
     */
    public static function valid(
        Money $unitCost,
        Money $totalCost,
        CostSource $costSource,
        string $costSourceReference = '',
        array $warnings = [],
    ): self {
        return new self(
            isValid: true,
            message: 'Cost preview available',
            unitCost: $unitCost,
            totalCost: $totalCost,
            costSource: $costSource,
            costSourceReference: $costSourceReference,
            warnings: $warnings
        );
    }

    /**
     * Create an invalid cost preview result
     */
    public static function invalid(
        string $message,
        array $suggestedActions = [],
    ): self {
        return new self(
            isValid: false,
            message: $message,
            suggestedActions: $suggestedActions
        );
    }
}
