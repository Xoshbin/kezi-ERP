<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

/**
 * Data Transfer Object for inventory movement validation results
 *
 * Contains comprehensive validation information including errors,
 * warnings, and requirements for inventory movements.
 */
readonly class InventoryMovementValidationResult
{
    public function __construct(
        public bool $isValid,
        public string $status, // 'success', 'warning', 'failed'
        public array $errors = [],
        public array $warnings = [],
        public array $requirements = [],
    ) {}

    /**
     * Check if the validation passed
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if there are warnings
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Check if there are errors
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get minimum requirements
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Get validation status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Create a successful validation result
     */
    public static function success(): self
    {
        return new self(
            isValid: true,
            status: 'success'
        );
    }

    /**
     * Create a validation result with warnings
     */
    public static function warning(array $warnings): self
    {
        return new self(
            isValid: true,
            status: 'warning',
            warnings: $warnings
        );
    }

    /**
     * Create a failed validation result
     */
    public static function failed(
        array $errors,
        array $warnings = [],
        array $requirements = [],
    ): self {
        return new self(
            isValid: false,
            status: 'failed',
            errors: $errors,
            warnings: $warnings,
            requirements: $requirements
        );
    }

    /**
     * Get a summary message
     */
    public function getSummary(): string
    {
        if ($this->status === 'success') {
            return 'Validation passed successfully';
        }

        if ($this->status === 'warning') {
            $warningCount = count($this->warnings);

            return "Validation passed with {$warningCount} warning(s)";
        }

        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);

        $summary = "Validation failed with {$errorCount} error(s)";
        if ($warningCount > 0) {
            $summary .= " and {$warningCount} warning(s)";
        }

        return $summary;
    }

    /**
     * Get formatted validation report
     */
    public function getReport(): array
    {
        $report = [
            'status' => $this->status,
            'is_valid' => $this->isValid,
            'summary' => $this->getSummary(),
        ];

        if (! empty($this->errors)) {
            $report['errors'] = $this->errors;
        }

        if (! empty($this->warnings)) {
            $report['warnings'] = $this->warnings;
        }

        if (! empty($this->requirements)) {
            $report['requirements'] = $this->requirements;
        }

        return $report;
    }
}
