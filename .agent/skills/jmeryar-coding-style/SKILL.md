---
name: kezi-coding-style
description: Official coding styles, architectural patterns, and best practices. Use when writing or refactoring code.
---

### 💎 **Core Coding Principles**

<principles>
- **Immutability:** Posted records are NEVER edited. Reversals only. (No `DELETE` or `UPDATE` on posted financial documents).
- **Service-Action-DTO:** Business logic in Actions, Orchestration in Services. Input via DTOs.
- **Money Objects:** `Brick\Money` for 100% precision. Never use floats for financial data.
- **TDD:** Pest for all business logic (services, calculations, state changes) and UI workflows.
- **PHP 8.1+ Enums:** Backed enums for all states. Cases: PascalCase, Values: snake_case.
- **Manual Control:** System relies on manual input; no hidden automatic payment processor logic.
</principles>

### 🏗️ **Layered Architecture Rules**

<layers>
#### Actions (`app/Actions/`)
- Atomic operations wrapped in `DB::transaction()`.
- Input via DTOs.
- Clear `execute()` method.

#### DTOs (`app/DataTransferObjects/`)
- `readonly` classes with typed properties.
- No business logic.

#### Services (`app/Services/`)
- High-level orchestration.
- Dispatch domain events.
</layers>

## 6. Financial Calculations

**Rule:** All monetary values **MUST** be handled using `Brick\Money` objects. Never use floats.

```php
use Brick\Money\Money;

// Correct usage
$total = Money::zero('IQD');
foreach ($lines as $line) {
    $total = $total->plus($line->amount);
}

// Never do this
// $total = 0.0;
// $total += $line->amount->getAmount()->toFloat();
```

## 7. Namespace Conventions

All package code uses the `Kezi\{ModuleName}` namespace:

```php
// Models
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Partner;

// Actions
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;

// Services
use Kezi\Accounting\Services\JournalEntryService;

// DTOs
use Kezi\Accounting\DataTransferObjects\CreateJournalEntryDTO;

// Enums
use Kezi\Accounting\Enums\InvoiceStatus;
```

## 8. Internationalization and Localization

**Rule:** All UI strings **MUST** be localized and scoped to their respective modules.

- **No Global Translations:** Do not use `lang/en.json`, `lang/ckb.json`, or any global translation files at the root level. All translations must reside within the modules.
- **Modular Scoping:** Use the `module::file.key` syntax for all translations (e.g., `__('accounting::cheque.label')`).
- **No Hardcoded Strings:** UI strings (labels, titles, placeholders, helper texts, notifications, buttons) **MUST NOT** be hardcoded.
- **Consistency Across Locales:** Ensure translation keys exist and are translated in all supported locales:
    - `en` (English)
    - `ckb` (Kurdish Sorani)
    - `ar` (Arabic)
- **Filament Integration:** Always use translation keys in Filament components:
    - `getModelLabel()`, `getPluralModelLabel()`
    - `getNavigationLabel()`, `getNavigationGroup()`
    - `label()`, `placeholder()`, `helperText()`
    - `Action::make('name')->label(__('module::file.key'))`
- **Enum Translations:** Translate enum values in the UI using the `module::file.enum_case` pattern or by implementing a `getLabel()` method on the enum that returns `__('module::file.status.' . $this->value)`.
- **Naming Convention:** Translation files within `packages/kezi/{package}/resources/lang/{locale}/` should be named after the feature or entity they describe (e.g., `cash_advance.php` for HR cash advances).

## 9. Filament Cluster Navigation

**Rule:** Module settings/configuration resources **MUST** be registered into the central `SettingsCluster` while remaining in their original module directories.

### 9.1. Cluster Injection Pattern

Configuration resources (e.g., `FiscalYearResource`, `TaxResource`, `DepartmentResource`) should use the **Cluster Injection** pattern:

```php
// In your resource file
use Kezi\Foundation\Filament\Clusters\Settings\SettingsCluster;

class FiscalYearResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.groups.accounting_settings');
    }
}
```

### 9.2. Translation Requirements

Add navigation group translations to `Modules/{Module}/resources/lang/{locale}/navigation.php`:

```php
// packages/kezi/accounting/resources/lang/en/navigation.php
return [
    'groups' => [
        'accounting_settings' => 'Accounting',
    ],
];
```

### 9.3. Cluster Label Translations

Cluster classes **MUST** use module-scoped translation keys:

```php
// SettingsCluster uses Foundation module translations
public static function getNavigationLabel(): string
{
    return __('foundation::navigation.clusters.settings');
}

// InventoryCluster uses Inventory module translations
public static function getNavigationLabel(): string
{
    return __('inventory::navigation.clusters.inventory');
}
```

Ensure `clusters` key exists in the module's `navigation.php`:

```php
// packages/kezi/foundation/resources/lang/en/navigation.php
return [
    'groups' => [
        'general_settings' => 'General',
    ],
    'clusters' => [
        'settings' => 'Settings',
    ],
];
```

### 9.4. Resource Categorization

| Category | Cluster | Examples |
|----------|---------|----------|
| Configuration | SettingsCluster | Fiscal Years, Taxes, Journals, Accounts, Departments, Leave Types, Work Centers |
| Operational | Original Module Cluster | Invoices, Bills, Payments, Journal Entries, Stock Moves |

## 10. Documentation Standards
 
 **Rule:** All documentation **MUST** follow the **Diátaxis framework**, which categorizes documentation into four distinct quadrants based on user needs. See [docs/DOCUMENTATION_STANDARD.md](../../docs/DOCUMENTATION_STANDARD.md) for the complete guide.
 
 ### 10.1. The 4 Quadrants
 
 | Quadrant | Goal | Tone | Directory |
 | :--- | :--- | :--- | :--- |
 | **1. Tutorials** | *Learning*: Guide beginners through a complete workflow. | Teacher ("Let's do this") | `docs/tutorials/` |
 | **2. How-to Guides** | *Task*: Solve a specific problem for a user who is stuck. | Helper ("How to X") | `docs/how-to/` |
 | **3. Reference** | *Information*: Technical facts and specs (APIs, Classes). | Neutral/Dictionary | `docs/reference/` |
 | **4. Explanation** | *Understanding*: Context, history, and design decisions. | Architect/Expert | `docs/explanation/` |
 
 ### 10.2. Writing Style
 
 - **Conversational Tone:** Especially for Tutorials and How-to guides, write as if explaining to a smart friend.
 - **Plain Language:** Avoid jargon; explain technical terms immediately.
 - **Visual Aids:** Use emojis, ASCII diagrams, and tables.
 - **Real Examples:** Provide concrete, realistic business scenarios.
 
 ### 10.3. Required Elements (User Guides)
 
 Most "User Guides" fall into **How-to** or **Tutorials**. Ensure they include:
 
 | Section | Purpose |
 |---------|---------|
 | "What is...?" | Explain the concept in simple terms |
 | "Where to Find It" | Navigation instructions with bold menu paths |
 | Step-by-Step Guide | Numbered steps with field descriptions |
 | Troubleshooting | Q&A format for common issues |
 | Related Docs | Links to related quadrants |

### 10.3. Formatting Rules

- **Bold** for menu items, buttons, and field names
- Use `→` arrows between menu levels (e.g., **Accounting → Invoices**)
- Include "In plain English" translations for accounting concepts
- Use GitHub-style alerts (`[!TIP]`, `[!WARNING]`, etc.) for callouts

### 10.4. Attaching Docs to Filament Pages

**Rule:** Each user guide **MUST** be attached to its corresponding Filament resource or page using `DocsAction`.

**Implementation:**

```php
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListPayments extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('payments'), // Links to docs/User Guide/payments.md
        ];
    }
}
```

**Conventions:**
- Add `DocsAction` to the `getHeaderActions()` method on List pages
- The parameter matches the slug key defined in `DocsAction::mapSlugToDocumentationPath()`
- **IMPORTANT**: You **MUST** add a mapping entry in `Kezi\Foundation\Filament\Actions\DocsAction.php` pointing your slug to the correct file path (e.g., `'my-slug' => 'User Guide/my-file'`).
- Users can click the Help/Docs button in the header to open the guide

### 10.5. Reference

See [docs/DOCUMENTATION_STANDARD.md](../../docs/DOCUMENTATION_STANDARD.md) for the complete style guide with templates and examples.

### 10.6. Localization/Translation Naming

**Rule:** Translated documentation files **MUST** be placed in the same directory as the original English file and use the language code as a suffix.

**Convention:** `filename.language_code.md` (e.g., `trial-balance-report.ckb.md`, `vendor-bills.ar.md`).

**Do NOT:**
- Create `ckb/` or `ar/` subdirectories.
- Rename the file completely (e.g., `kurdish-report.md`).

## 11. Continuous Quality Improvement (PHPStan)

**Strategy:** We are systematically resolving PHPStan errors across the entire project. This is an ongoing process where we decrement the total error count with each task we work on.

**Rules:**
- **Zero Regression:** Never introduce new PHPStan errors into a module that has been cleared.
- **Incremental Cleanup:** When working on a module (e.g., HR, Sales, Accounting), allocate time to resolve existing PHPStan errors in that module's code and tests.
- **Forced Baseline Maintenance:** As errors are resolved, the baseline MUST be cleared to prevent "outdated error" reports.
  1. Fix the bug/error.
  2. Run `./vendor/bin/phpstan analyse --generate-baseline` to overwrite and shrink the baseline.
  3. The goal is to reach a zero-baseline state for all modules.
- **Explicit Type Hints:** Use PHPDoc (`/** @var ... */`) and class-level docblocks in `TestCase` to resolve "undefined property" errors without breaking runtime inheritance.
- **Goal:** Our objective is to eventually eliminate all PHPStan errors and maintain a "No errors" state for all modules.

## 12. Testing Strategy

**Strategy:** We adopt a "Pyramid" testing strategy that prioritizes speed and reliability. **You MUST run `php artisan test --parallel` before finishing any task.**

### 12.1. Pyramid Layers

1.  **Filament Feature Tests (95% - Primary):**
    *   **Scope:** Validation, Permissions, Business Logic, Form State, Action Execution, Database/State changes.
    *   **Why:** Fast (milliseconds), deterministic, runs in database transactions.
    *   **Tool:** Pest + Filament Testing Plugin.
    *   **Rule:** If it can be tested with Filament `assertFormSet`, `assertActionCalled`, or `assertSee`, do it here.

2.  **Unit Tests (Support):**
    *   **Scope:** Complex calculations, Service logic, Model scopes, independent utilities.
    *   **Why:** Extremely fast, isolated.

3.  **Browser Tests (Smoke Tests Only):**
    *   **Scope:** "Smoke" tests only. 2-3 tests per critical module max.
    *   **Purpose:** Prove the JavaScript didn't crash, the page loads, and critical frontend-only interactions work.
    *   **Rule:** DO NOT use for general feature verification. Only use for end-to-end critical path validation where backend tests are insufficient.

### 12.2. Filament Test Example

```php
// packages/kezi/sales/tests/Feature/Filament/CreateInvoiceTest.php
use function Pest\Livewire\livewire;

it('can create an invoice', function () {
    livewire(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1]
            ]
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('invoices', ['customer_id' => $customer->id]);
});
```

## 13. Progressive Disclosure UI Pattern

**Rule:** When implementing complex line items (Repeaters) that exceed 5-6 core fields or cause horizontal overflow, use the **Progressive Disclosure** pattern.

### 13.1. Concept
- **Essential Fields:** Display only the absolute minimum fields required for the 90% use case directly in the table (e.g., Product, Quantity, Price).
- **Advanced Fields:** Move secondary or specialized accounting fields (e.g., Deferred Dates, Shipping Types, Asset Categories) into a **Slide-Over Drawer**.
- **Access:** Add an "Advanced Settings" action to each line item using `extraItemActions()`.

### 13.2. Implementation (Filament 4)

Use `extraItemActions()` with a `slideOver()` action. Ensure state is persisted correctly.

```php
Repeater::make('lines')
    ->table([
        TableColumn::make('product_id')->width('20%'),
        // ... only 5-6 essential columns
    ])
    ->extraItemActions([
        \Filament\Actions\Action::make('advanced_settings')
            ->label(__('Advanced Settings'))
            ->icon('heroicon-m-cog-6-tooth')
            ->slideOver()
            ->form([
                Section::make('Deferred Accounting')
                    ->schema([
                        DatePicker::make('deferred_start_date'),
                        DatePicker::make('deferred_end_date'),
                    ])->columns(2),
            ])
            ->fillForm(fn (Repeater $component, array $arguments) => $component->getRawItemState($arguments['item']))
            ->action(function (array $data, Repeater $component, array $arguments) {
                $item = $arguments['item'];
                $state = $component->getState();
                $state[$item] = array_merge($state[$item], $data);
                $component->state($state);
            }),
    ])
```

### 13.3. Real Example: Vendor Bill Lines
The `VendorBillResource.php` implements this pattern to keep the billing interface clean while supporting complex landed costs and asset acquisitions.

