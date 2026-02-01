<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Acme Corp',
            'address' => '123 Test Street, Test City',
            'tax_id' => strtoupper(Str::random(10)),
            // Let Laravel handle creation unless specified otherwise in the test.
            'currency_id' => function () {
                $currency = \Kezi\Foundation\Models\Currency::firstOrCreate(
                    ['code' => 'IQD'],
                    [
                        'name' => 'Iraqi Dinar',
                        'symbol' => 'IQD',
                        'is_active' => true,
                        'decimal_places' => 3,
                    ]
                );

                return $currency->id;
            },
            'fiscal_country' => 'IQ', // Default to Iraq as per project spec
            'parent_company_id' => null,
            'enable_reconciliation' => false, // Default to disabled for security
            'inventory_accounting_mode' => InventoryAccountingMode::getDefault()->value,
        ];
    }

    /**
     * Indicate that the company has reconciliation enabled.
     */
    public function withReconciliationEnabled(): static
    {
        return $this->state(fn () => [
            'enable_reconciliation' => true,
        ]);
    }
}
