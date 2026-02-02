---
title: Project Management & Job Costing Architecture
icon: heroicon-o-cog
order: 13
---

# Project Management & Job Costing Architecture

This developer guide explains the **Project Management module** architecture—how projects, tasks, timesheets, budgets, and invoicing work together. It covers the data model, service layer, integration points, and design decisions.

---

## Module Overview

The Project Management module provides job costing functionality:

| Feature | What It Does | Key Models |
|---------|--------------|------------|
| **Projects** | Container for all work | `Project`, `ProjectTask` |
| **Timesheets** | Employee time tracking | `Timesheet`, `TimesheetLine` |
| **Budgets** | Cost planning & tracking | `ProjectBudget`, `ProjectBudgetLine` |
| **Invoicing** | Client billing | `ProjectInvoice` |

**Module Location:** `Modules/ProjectManagement/`

---

## Core Data Model

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              PROJECT                                        │
│  The central entity connecting everything                                   │
│  ─────────────────────────────────────────────                             │
│  - name, description                                                        │
│  - customer_id → Partner                                                    │
│  - manager_id → User                                                        │
│  - status (Enum: draft, in_progress, completed, cancelled)                 │
│  - billing_type (Enum: fixed, time_and_materials, non_billable)           │
│  - hourly_rate (for T&M billing)                                           │
│  - start_date, end_date                                                     │
└────────────────────────────────┬─────┬─────┬─────┬──────────────────────────┘
                                 │     │     │     │
         ┌───────────────────────┘     │     │     └───────────────────────┐
         ▼                             ▼     ▼                             ▼
┌─────────────────────┐   ┌─────────────────────┐   ┌─────────────────────┐
│   PROJECT TASK      │   │     TIMESHEET       │   │   PROJECT BUDGET    │
│   ───────────────── │   │   ───────────────── │   │   ───────────────── │
│   - name            │   │   - employee_id      │   │   - name            │
│   - assigned_to     │   │   - start_date       │   │   - total_budget    │
│   - estimated_hours │   │   - end_date         │   │   - total_actual    │
│   - progress (0-100)│   │   - status (Enum)    │   │                     │
│   - status (Enum)   │   │   - total_hours      │   │         │           │
│         │           │   │         │            │   │         ▼           │
│         ▼           │   │         ▼            │   │ ┌─────────────────┐ │
│   [TimesheetLine    │   │ ┌─────────────────┐  │   │ │ BUDGET LINE     │ │
│    references task] │   │ │ TIMESHEET LINE  │  │   │ │ - category      │ │
└─────────────────────┘   │ │ - project_id    │  │   │ │ - budgeted_amt  │ │
                          │ │ - task_id       │  │   │ │ - actual_amt    │ │
                          │ │ - date          │  │   │ └─────────────────┘ │
                          │ │ - hours         │  │   └─────────────────────┘
                          │ │ - is_billable   │  │
                          │ │ - description   │  │
                          │ └─────────────────┘  │
                          └──────────────────────┘
```

---

## Model Details

### Project (`Modules/ProjectManagement/app/Models/Project.php`)

The central entity representing client work.

**Key Relationships:**
```php
public function customer(): BelongsTo
public function manager(): BelongsTo
public function tasks(): HasMany
public function timesheets(): HasManyThrough
public function timesheetLines(): HasMany
public function budgets(): HasMany
public function invoices(): HasMany
```

**Key Attributes:**
- `status` → `ProjectStatus` enum (draft, in_progress, completed, cancelled)
- `billing_type` → `BillingType` enum (fixed, time_and_materials, non_billable)
- `hourly_rate` → Stored in minor units via `BaseCurrencyMoneyCast`
- `total_budget` → Stored in minor units via `BaseCurrencyMoneyCast`

**Computed Properties:**
```php
public function getTotalBillableHours(): float
{
    return $this->timesheetLines()
        ->whereHas('timesheet', fn ($q) => $q->where('status', TimesheetStatus::Approved))
        ->where('is_billable', true)
        ->sum('hours');
}

public function getTotalActualCost(): Money
{
    // Aggregates from approved timesheets and project-tagged expenses
}
```

---

### ProjectTask (`Modules/ProjectManagement/app/Models/ProjectTask.php`)

Work breakdown within a project.

**Key Fields:**
- `project_id` → Parent project
- `assigned_to` → User responsible
- `estimated_hours`, `actual_hours` → Effort tracking
- `progress` → 0-100 percentage
- `status` → `TaskStatus` enum (todo, in_progress, completed, cancelled)

**Key Relationships:**
```php
public function project(): BelongsTo
public function assignee(): BelongsTo
public function timesheetLines(): HasMany
```

---

### Timesheet (`Modules/ProjectManagement/app/Models/Timesheet.php`)

Weekly/periodic collection of time entries.

**Key Fields:**
- `employee_id` → Employee submitting time
- `start_date`, `end_date` → Period covered
- `status` → `TimesheetStatus` enum (draft, submitted, approved, rejected)
- `total_hours` → Computed sum of lines
- `approved_by`, `approved_at` → Approval metadata
- `rejection_reason` → If rejected

**Relationships:**
```php
public function employee(): BelongsTo
public function approvedBy(): BelongsTo
public function lines(): HasMany
```

---

### TimesheetLine (`Modules/ProjectManagement/app/Models/TimesheetLine.php`)

Individual time entry.

**Key Fields:**
- `timesheet_id` → Parent timesheet
- `project_id` → Which project
- `project_task_id` → Which task (optional but recommended)
- `date` → When work was done
- `hours` → Duration
- `description` → Work details
- `is_billable` → Can be charged to client?

**Relationships:**
```php
public function timesheet(): BelongsTo
public function project(): BelongsTo
public function projectTask(): BelongsTo
```

---

### ProjectBudget & ProjectBudgetLine

Budget planning and tracking.

**ProjectBudget:**
- `project_id` → Parent project
- `name` → Budget name (e.g., "Initial Estimate")
- `total_budget` → Sum of line budgets (Money)
- `total_actual` → Sum of actual costs (Money)

**ProjectBudgetLine:**
- `budget_id` → Parent budget
- `category` → Cost category name
- `budgeted_amount` → Planned cost (Money)
- `actual_amount` → Actual cost (Money)

---

## Enums

**Location:** `Modules/ProjectManagement/app/Enums/`

### ProjectStatus
```php
enum ProjectStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

### TaskStatus
```php
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

### TimesheetStatus
```php
enum TimesheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

### BillingType
```php
enum BillingType: string
{
    case Fixed = 'fixed';
    case TimeAndMaterials = 'time_and_materials';
    case NonBillable = 'non_billable';
}
```

---

## Actions Layer

All write operations go through Actions following the Command pattern.

**Location:** `Modules/ProjectManagement/app/Actions/`

### Project Actions

| Action | Purpose | DTO |
|--------|---------|-----|
| `CreateProjectAction` | Create new project | `CreateProjectDTO` |
| `UpdateProjectAction` | Update project | `UpdateProjectDTO` |

### Timesheet Actions

| Action | Purpose | DTO |
|--------|---------|-----|
| `CreateTimesheetAction` | Create timesheet with lines | `CreateTimesheetDTO` + `TimesheetLineDTO[]` |
| `SubmitTimesheetAction` | Move to "submitted" status | N/A |
| `ApproveTimesheetAction` | Approve and lock | N/A |
| `RejectTimesheetAction` | Reject with reason | N/A |

### Budget Actions

| Action | Purpose | DTO |
|--------|---------|-----|
| `CreateProjectBudgetAction` | Create budget with lines | `CreateProjectBudgetDTO` + `ProjectBudgetLineDTO[]` |

### Invoice Actions

| Action | Purpose | DTO |
|--------|---------|-----|
| `CreateProjectInvoiceAction` | Generate client invoice from project | `CreateProjectInvoiceDTO` |

---

## DTOs (Data Transfer Objects)

All DTOs are `readonly` classes with strict typing.

**Location:** `Modules/ProjectManagement/app/DataTransferObjects/`

### Example: CreateTimesheetDTO
```php
readonly class CreateTimesheetDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public CarbonImmutable $start_date,
        public CarbonImmutable $end_date,
        public TimesheetStatus $status = TimesheetStatus::Draft,
        /** @var array<TimesheetLineDTO> */
        public array $lines = [],
    ) {}
}
```

### Example: TimesheetLineDTO
```php
readonly class TimesheetLineDTO
{
    public function __construct(
        public int $project_id,
        public ?int $project_task_id,
        public CarbonImmutable $date,
        public float $hours,
        public ?string $description = null,
        public bool $is_billable = true,
    ) {}
}
```

---

## Services Layer

Services orchestrate complex workflows.

**Location:** `Modules/ProjectManagement/app/Services/`

### TimesheetService

Manages the timesheet submission/approval workflow.

```php
class TimesheetService
{
    public function createTimesheet(CreateTimesheetDTO $dto): Timesheet
    public function submitTimesheet(Timesheet $timesheet): void
    public function approveTimesheet(Timesheet $timesheet, User $approver): void
    public function rejectTimesheet(Timesheet $timesheet, User $rejector, string $reason): void
}
```

### ProjectBudgetService

Handles budget calculations and variance analysis.

```php
class ProjectBudgetService
{
    public function createBudget(CreateProjectBudgetDTO $dto): ProjectBudget
    public function calculateActuals(ProjectBudget $budget): Money
    public function getVarianceByCategory(ProjectBudget $budget): array
}
```

### ProjectCostingService

Aggregates costs from multiple sources.

```php
class ProjectCostingService
{
    public function getTotalLaborCost(Project $project): Money
    public function getTotalMaterialCost(Project $project): Money
    public function getTotalCost(Project $project): Money
}
```

### ProjectInvoicingService

Generates customer invoices from project data.

```php
class ProjectInvoicingService
{
    public function generateInvoice(CreateProjectInvoiceDTO $dto): ProjectInvoice
    public function calculateBillableAmount(Project $project): Money
}
```

---

## Filament UI

The module uses **Filament Clusters** for UI organization.

**Cluster Location:** `Modules/ProjectManagement/app/Filament/Clusters/ProjectManagement/`

### Resources

| Resource | Path | Purpose |
|----------|------|---------|
| `ProjectResource` | `Resources/Projects/` | Project CRUD |
| `TimesheetResource` | `Resources/Timesheets/` | Timesheet CRUD + workflow |
| `ProjectTaskResource` | `Resources/ProjectTasks/` | Task management |
| `ProjectBudgetResource` | `Resources/ProjectBudgets/` | Budget CRUD |
| `ProjectInvoiceResource` | `Resources/ProjectInvoices/` | Invoice generation |

### Resource Structure

Each resource follows a consistent folder structure:

```
Resources/
└── Timesheets/
    ├── TimesheetResource.php       # Main resource class
    ├── Pages/
    │   ├── CreateTimesheet.php    # Create page
    │   ├── EditTimesheet.php      # Edit page
    │   └── ListTimesheets.php     # List page
    ├── Schemas/
    │   └── TimesheetForm.php      # Form schema
    └── Tables/
        └── TimesheetsTable.php    # Table schema
```

### Custom Table Actions

Timesheets include workflow actions:

```php
Action::make('submit')
    ->visible(fn ($record) => $record->status === TimesheetStatus::Draft)
    ->action(fn ($record, TimesheetService $service) => $service->submitTimesheet($record))

Action::make('approve')
    ->visible(fn ($record) => $record->status === TimesheetStatus::Submitted)
    ->action(fn ($record, TimesheetService $service) => $service->approveTimesheet($record, auth()->user()))

Action::make('reject')
    ->form([Textarea::make('reason')->required()])
    ->visible(fn ($record) => $record->status === TimesheetStatus::Submitted)
    ->action(fn ($record, array $data, TimesheetService $service) => 
        $service->rejectTimesheet($record, auth()->user(), $data['reason']))
```

---

## Key Integration Points

### 1. Multi-Tenancy (Company Scope)

All models are company-scoped:

```php
// Migration pattern
$table->foreignId('company_id')->constrained();

// Model boot pattern
protected static function booted(): void
{
    static::addGlobalScope(new CompanyOwned);
}
```

### 2. Money Handling

All monetary fields use `Brick\Money\Money`:

```php
// Cast definition
protected $casts = [
    'hourly_rate' => BaseCurrencyMoneyCast::class,
    'total_budget' => BaseCurrencyMoneyCast::class,
];
```

### 3. Partner Integration

Projects link to customers via the Foundation module's `Partner` model:

```php
public function customer(): BelongsTo
{
    return $this->belongsTo(Partner::class, 'customer_id');
}
```

### 4. User/Employee Integration

Timesheets link to employees:

```php
public function employee(): BelongsTo
{
    return $this->belongsTo(Employee::class);
}
```

---

## Testing Patterns

### Feature Tests

**Location:** `Modules/ProjectManagement/tests/Feature/`

```php
it('creates a project with all required fields', function () {
    $dto = new CreateProjectDTO(
        company_id: $this->company->id,
        name: 'Test Project',
        customer_id: $this->customer->id,
        // ... other fields
    );
    
    $project = app(CreateProjectAction::class)->execute($dto);
    
    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->name)->toBe('Test Project');
});
```

### Filament Tests

**Location:** `Modules/ProjectManagement/tests/Feature/Filament/`

Key pattern for Repeater testing:

```php
it('can create a timesheet with lines', function () {
    // IMPORTANT: Disable UUID generation for test data filling
    $undoRepeaterFake = \Filament\Forms\Components\Repeater::fake();
    
    livewire(CreateTimesheet::class)
        ->fillForm([
            'employee_id' => $this->employee->id,
            'lines' => [
                ['project_id' => $this->project->id, 'hours' => 8, ...]
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();
    
    $undoRepeaterFake();
});
```

---

## Filament v4 Specifics

### Schema Components

Use correct namespaces for Filament v4:

```php
// Layout components
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

// Form components
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;

// Get utility for reactive forms
use Filament\Schemas\Components\Utilities\Get;
```

### Repeater Data Injection

When Repeater creates relationship records, inject company_id:

```php
Repeater::make('lines')
    ->relationship()
    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
        $data['company_id'] = Filament::getTenant()?->id;
        return $data;
    })
```

---

## File Reference

### Models
- `Modules/ProjectManagement/app/Models/Project.php`
- `Modules/ProjectManagement/app/Models/ProjectTask.php`
- `Modules/ProjectManagement/app/Models/Timesheet.php`
- `Modules/ProjectManagement/app/Models/TimesheetLine.php`
- `Modules/ProjectManagement/app/Models/ProjectBudget.php`
- `Modules/ProjectManagement/app/Models/ProjectBudgetLine.php`
- `Modules/ProjectManagement/app/Models/ProjectInvoice.php`

### Enums
- `Modules/ProjectManagement/app/Enums/ProjectStatus.php`
- `Modules/ProjectManagement/app/Enums/TaskStatus.php`
- `Modules/ProjectManagement/app/Enums/TimesheetStatus.php`
- `Modules/ProjectManagement/app/Enums/BillingType.php`

### Actions
- `Modules/ProjectManagement/app/Actions/CreateProjectAction.php`
- `Modules/ProjectManagement/app/Actions/CreateTimesheetAction.php`
- `Modules/ProjectManagement/app/Actions/SubmitTimesheetAction.php`
- `Modules/ProjectManagement/app/Actions/ApproveTimesheetAction.php`
- `Modules/ProjectManagement/app/Actions/RejectTimesheetAction.php`
- `Modules/ProjectManagement/app/Actions/CreateProjectBudgetAction.php`
- `Modules/ProjectManagement/app/Actions/CreateProjectInvoiceAction.php`

### Services
- `Modules/ProjectManagement/app/Services/TimesheetService.php`
- `Modules/ProjectManagement/app/Services/ProjectBudgetService.php`
- `Modules/ProjectManagement/app/Services/ProjectCostingService.php`
- `Modules/ProjectManagement/app/Services/ProjectInvoicingService.php`

### Factories
- `Modules/ProjectManagement/database/factories/ProjectFactory.php`
- `Modules/ProjectManagement/database/factories/ProjectTaskFactory.php`
- `Modules/ProjectManagement/database/factories/TimesheetFactory.php`
- `Modules/ProjectManagement/database/factories/TimesheetLineFactory.php`
- `Modules/ProjectManagement/database/factories/ProjectBudgetFactory.php`

---

## Related Documentation

- [Project Management User Guide](../User%20Guide/project-management.md) - End-user guide
- [Timesheet Tracking](../User%20Guide/timesheet-tracking.md) - Time entry guide
- [Project Budgeting](../User%20Guide/project-budgeting.md) - Budget user guide
- [Filament Testing Guide](filament_testing.md) - Testing patterns
