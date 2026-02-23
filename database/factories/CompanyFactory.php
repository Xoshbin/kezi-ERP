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
            'currency_id' => fn () => \Kezi\Foundation\Models\Currency::factory()->createSafely(),
            'fiscal_country' => 'IQ', // Default to Iraq as per project spec
            'parent_company_id' => null,
            'enable_reconciliation' => false, // Default to disabled for security
            'inventory_accounting_mode' => InventoryAccountingMode::getDefault()->value,
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Company $company) {
            \Kezi\Foundation\Models\Partner::create([
                'company_id' => $company->id,
                'name' => 'Walk-in Customer',
                'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Customer,
                'is_active' => true,
            ]);
        });
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
