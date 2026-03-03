---
name: kezi-architecture
description: Technical architecture, patterns (Service-Action-DTO), and domain-specific workflows. Use when designing or implementing features.
---

# Kezi ERP Architecture

## Project Structure

This is an **ERP accounting system** built on Laravel 12 with Filament 4, organized into domain-specific modules.

### Modular Package Architecture

The system is built as a **Modular Monolith** using local PHP packages managed by Composer path repositories:

```
packages/kezi/
├── accounting/          # Core accounting engine
├── foundation/          # Shared infrastructure (Partners, Companies, Currencies)
├── sales/               # Customer invoices, quotes
├── purchase/            # Vendor bills, purchase orders
├── inventory/           # Stock management, valuation
├── hr/                  # Employees, payroll, attendance
├── payment/             # Payment processing
├── product/             # Product catalog
├── project-management/  # Projects, timesheets
├── manufacturing/       # BOMs, manufacturing orders
└── quality-control/     # Quality checks
```

### Module/Package Structure

Each package follows this structure:

```
packages/kezi/{package-name}/
├── app/
│   ├── Actions/              # Business operations (Command Pattern)
│   ├── DataTransferObjects/  # Immutable data contracts
│   ├── Services/             # Business orchestration
│   ├── Models/               # Eloquent models
│   ├── Enums/                # State management (PHP 8.1+ backed enums)
│   ├── Filament/             # Admin panel resources
│   ├── Observers/            # Model lifecycle reactions
│   ├── Policies/             # Authorization
│   ├── Events/               # Domain events
│   └── Listeners/            # Event handlers
├── database/
│   ├── migrations/
│   └── factories/
├── tests/
│   ├── Feature/
│   └── Unit/
└── resources/
```

### 🏗️ **Architectural Patterns**

<patterns>
#### Service-Action-DTO Pattern
- **Actions** (`app/Actions/`) - Atomic business operations (Command Pattern).
- **DTOs** (`app/DataTransferObjects/`) - Immutable, type-safe data contracts.
- **Services** (`app/Services/`) - Business orchestration and domain logic.

#### Money Handling (Critical)
- Use `Brick\Money\Money` objects throughout - **never floats**.
- Custom `MoneyCast` for database storage.
- Compare Money objects with `isEqualTo()`, never `==`.
</patterns>

### 🛠️ **Module Organization**

<structure>
Each module follows this structure to ensure consistency:
1. **Actions/**: Domain-specific business logic.
2. **DTOs/**: Type-safe data containers.
3. **Services/**: Application-level orchestration.
4. **Models/**: Domain entities.
5. **Filament/**: Admin panel resources and clusters.
</structure>

### 🔐 **Domain-Specific Workflows**

<workflows>
#### Journal Entry Creation
All financial transactions MUST create journal entries through `CreateJournalEntryAction`. This ensures:
- Period lock validation via `LockDateService`.
- Debit/Credit balancing.
- Cryptographic hash chaining.
- Multi-currency revaluation if applicable.

#### Filament Settings Clusters
Module configuration resources MUST be registered into the central `SettingsCluster` for a unified user experience.
</workflows>
