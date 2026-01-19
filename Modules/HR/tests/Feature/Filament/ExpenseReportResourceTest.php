<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\ExpenseReportResource;
use Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\CreateExpenseReport;
use Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\EditExpenseReport;
use Modules\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\ListExpenseReports;
use Modules\HR\Models\CashAdvance;
use Modules\HR\Models\ExpenseReport;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('ExpenseReportResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(ExpenseReportResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(ExpenseReportResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list expense reports', function () {
        $expenseReports = ExpenseReport::factory()->count(3)->create(['company_id' => $this->company->id]);

        livewire(ListExpenseReports::class)
            ->assertCanSeeTableRecords($expenseReports);
    });

    it('can create expense report', function () {
        $cashAdvance = CashAdvance::factory()->create(['company_id' => $this->company->id]);
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => AccountType::Expense,
        ]);

        livewire(CreateExpenseReport::class, ['tenant' => $this->company->id])
            ->fillForm([
                'cash_advance_id' => $cashAdvance->id,
                'report_date' => now()->toDateString(),
                'notes' => 'Travel expenses',
                'lines' => [
                    [
                        'expense_account_id' => $account->id,
                        'expense_date' => now()->toDateString(),
                        'amount' => 100,
                        'description' => 'Taxi',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('expense_reports', [
            'company_id' => $this->company->id,
            'cash_advance_id' => $cashAdvance->id,
        ]);

        $this->assertDatabaseHas('expense_report_lines', [
            'expense_account_id' => $account->id,
            'description' => 'Taxi',
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateExpenseReport::class, ['tenant' => $this->company->id])
            ->fillForm([
                'cash_advance_id' => null,
                'report_date' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'cash_advance_id' => 'required',
                'report_date' => 'required',
            ]);
    });

    it('can edit expense report', function () {
        $expenseReport = ExpenseReport::factory()->create([
            'company_id' => $this->company->id,
            'notes' => 'Original Notes',
        ]);

        livewire(EditExpenseReport::class, ['record' => $expenseReport->getRouteKey()])
            ->fillForm([
                'notes' => 'Updated Notes',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($expenseReport->refresh()->notes)->toBe('Updated Notes');
    });

    it('can delete expense report via bulk action', function () {
        $expenseReport = ExpenseReport::factory()->create(['company_id' => $this->company->id]);

        livewire(ListExpenseReports::class)
            ->callTableBulkAction('delete', [$expenseReport]);

        $this->assertDatabaseMissing('expense_reports', [
            'id' => $expenseReport->id,
        ]);
    });
});
