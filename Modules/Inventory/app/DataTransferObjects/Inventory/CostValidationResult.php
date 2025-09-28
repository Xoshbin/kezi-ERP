<?php

namespace Modules\Inventory\DataTransferObjects\Inventory;

use Modules\Inventory\DataTransferObjects\Inventory\CostDeterminationResult;

/**
 * Data Transfer Object for cost validation results
 *
 * Contains the result of cost availability validation,
 * including success/failure status and guidance for resolution.
 */
readonly class CostValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $message,
        public ?CostDeterminationResult $costResult = null,
        public array $suggestedActions = [],
        public array $attemptedSources = [],
    ) {}

    /**
     * Check if cost validation was successful
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get the validation message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the cost determination result if validation was successful
     */
    public function getCostResult(): ?CostDeterminationResult
    {
        return $this->costResult;
    }

    /**
     * Get suggested actions for resolving validation failures
     */
    public function getSuggestedActions(): array
    {
        return $this->suggestedActions;
    }

    /**
     * Get attempted cost sources during validation
     */
    public function getAttemptedSources(): array
    {
        return $this->attemptedSources;
    }

    /**
     * Create a successful validation result
     */
    public static function success(
        string $message,
        ?CostDeterminationResult $costResult = null,
    ): self {
        return new self(
            isValid: true,
            message: $message,
            costResult: $costResult
        );
    }

    /**
     * Create a failed validation result
     */
    public static function failure(
        string $message,
        array $suggestedActions = [],
        array $attemptedSources = [],
    ): self {
        return new self(
            isValid: false,
            message: $message,
            suggestedActions: $suggestedActions,
            attemptedSources: $attemptedSources
        );
    }
}
