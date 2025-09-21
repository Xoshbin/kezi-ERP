<?php

namespace App\Enums\Inventory;

/**
 * InventoryAccountingMode Enum
 *
 * Defines how inventory journal entries are created when vendor bills are confirmed.
 * This setting allows companies to choose between immediate inventory recording
 * or manual inventory recording based on their operational needs.
 */
enum InventoryAccountingMode: string
{
    case AUTO_RECORD_ON_BILL = 'auto_record_on_bill';
    case MANUAL_INVENTORY_RECORDING = 'manual_inventory_recording';

    /**
     * Get the human-readable label for the inventory accounting mode.
     */
    public function label(): string
    {
        return match ($this) {
            self::AUTO_RECORD_ON_BILL => __('inventory_accounting.modes.auto_record_on_bill'),
            self::MANUAL_INVENTORY_RECORDING => __('inventory_accounting.modes.manual_inventory_recording'),
        };
    }

    /**
     * Get a detailed description of the inventory accounting mode.
     */
    public function description(): string
    {
        return match ($this) {
            self::AUTO_RECORD_ON_BILL => __('inventory_accounting.descriptions.auto_record_on_bill'),
            self::MANUAL_INVENTORY_RECORDING => __('inventory_accounting.descriptions.manual_inventory_recording'),
        };
    }

    /**
     * Get the target audience for this mode.
     */
    public function targetAudience(): string
    {
        return match ($this) {
            self::AUTO_RECORD_ON_BILL => __('inventory_accounting.target_audience.auto_record_on_bill'),
            self::MANUAL_INVENTORY_RECORDING => __('inventory_accounting.target_audience.manual_inventory_recording'),
        };
    }

    /**
     * Get the use case description for this mode.
     */
    public function useCase(): string
    {
        return match ($this) {
            self::AUTO_RECORD_ON_BILL => __('inventory_accounting.use_cases.auto_record_on_bill'),
            self::MANUAL_INVENTORY_RECORDING => __('inventory_accounting.use_cases.manual_inventory_recording'),
        };
    }

    /**
     * Get all available modes with their labels and descriptions.
     *
     * @return array<string, array{label: string, description: string, target_audience: string, use_case: string}>
     */
    public static function getOptionsWithDetails(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = [
                'label' => $mode->label(),
                'description' => $mode->description(),
                'target_audience' => $mode->targetAudience(),
                'use_case' => $mode->useCase(),
            ];
        }

        return $options;
    }

    /**
     * Get options formatted for Filament select components.
     *
     * @return array<string, string>
     */
    public static function getFilamentOptions(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }

    /**
     * Get the default inventory accounting mode for new companies.
     */
    public static function getDefault(): self
    {
        return self::AUTO_RECORD_ON_BILL;
    }

    /**
     * Check if this mode automatically creates inventory journal entries on bill confirmation.
     */
    public function autoRecordsInventory(): bool
    {
        return $this === self::AUTO_RECORD_ON_BILL;
    }

    /**
     * Check if this mode requires manual inventory recording.
     */
    public function requiresManualRecording(): bool
    {
        return $this === self::MANUAL_INVENTORY_RECORDING;
    }
}
