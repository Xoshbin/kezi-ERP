---
title: Tax Reporting Plugin Architecture
slug: tax-reporting-plugins
description: A guide to extending the tax reporting system with new country-specific generators.
author: System
---

# Tax Reporting Plugin Architecture

The Kezi ERP system uses a modular "plugin" architecture for generating tax reports. This allows developers to add support for new country-specific tax returns (e.g., Saudi VAT, UK VAT) without modifying the core reporting logic.

## Overview

The system revolves around the `TaxReportGeneratorContract`. Any class that implements this interface can serve as a generator for a tax report. The `TaxReportService` (and the UI) uses these generators to produce the final output.

### Key Components

1.  **Contract:** `Modules\Accounting\Services\Reports\Generators\TaxReportGeneratorContract`
2.  **Generators:** Classes implementing the contract (e.g., `IraqVATReturnGenerator`).
3.  **Registration:** The list of available generators is currently defined in the `TaxReports` Filament page (or can be moved to a service provider).

## How to Add a New Tax Report

Follow these steps to add a new tax report (e.g., for Saudi Arabia).

### 1. Create the Generator Class

Create a new class in `Modules/Accounting/app/Services/Reports/Generators/` that implements `TaxReportGeneratorContract`.

```php
namespace Modules\Accounting\Services\Reports\Generators;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\Models\JournalEntry;

class SaudiVATReturnGenerator implements TaxReportGeneratorContract
{
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        // 1. Fetch relevant Journal Entries
        // Use 'report_tag' on Tax Lines to filter and group data.
        
        // 2. Aggregate data into specific "Boxes" required by the ZATCA form.
        
        // 3. Return the structured array.
        return [
            'report_name' => 'Saudi VAT Return',
            'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            'currency' => $company->currency->code,
            'boxes' => [
                '1' => ['label' => 'Standard Rated Sales', 'value' => 1000.00],
                '2' => ['label' => 'Sales to Locals', 'value' => 500.00],
                // ...
            ],
        ];
    }
}
```

### 2. Map Taxes to Report Tags

The generator relies on the `report_tag` field in the `taxes` table. You must configure your taxes to use specific tags that your generator mimics.

-   **Example Tags for Saudi:** `KSA_SALES_STD`, `KSA_PURCHASE_STD`, `KSA_EXPORT_ZERO`.
-   **In the Generator:**
    ```php
    // In your generate method logic
    if ($line->tax->report_tag === 'KSA_SALES_STD') {
        // Add to Box 1
    }
    ```

### 3. Register the Generator

Currently, the available generators are listed in `Modules/Accounting/app/Filament/Pages/TaxReports.php`. Add your new class to the `getGenerators()` method.

```php
public function getGenerators(): array
{
    return [
        \Modules\Accounting\Services\Reports\Generators\IraqVATReturnGenerator::class => 'Iraq VAT Return',
        \Modules\Accounting\Services\Reports\Generators\SaudiVATReturnGenerator::class => 'Saudi VAT Return', // <--- Added
    ];
}
```

## Best Practices

-   **Use Brick\Money:** Always perform accumulations using `Brick\Money\Money` objects to avoid floating-point errors. Convert to float only at the very end for the return array.
-   **Immutable Entries:** Queries should strictly filter for `is_posted = true` to ensure the report reflects the immutable General Ledger.
-   **Traceability:** It is good practice to include a separate "details" section in your return array (if the UI supports it) listing the specific Journal Entry Lines that made up a box's total, for audit purposes.
