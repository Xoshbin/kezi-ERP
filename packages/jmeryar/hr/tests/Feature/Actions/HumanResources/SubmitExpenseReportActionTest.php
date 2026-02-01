<?php

namespace Jmeryar\HR\Tests\Feature\Actions\HumanResources;

use App\Models\User;
use Jmeryar\HR\Actions\HumanResources\SubmitExpenseReportAction;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\HR\Enums\ExpenseReportStatus;
use Jmeryar\HR\Models\CashAdvance;
use Jmeryar\HR\Models\ExpenseReport;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(SubmitExpenseReportAction::class);
});

it('can submit a draft expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Draft,
        'employee_id' => $this->user->id, // Assuming employee relation exists or can be inferred
    ]);

    $this->action->execute($expenseReport, $this->user);

    expect($expenseReport->refresh())
        ->status->toBe(ExpenseReportStatus::Submitted)
        ->submitted_at->not->toBeNull();
});

it('updates associated cash advance status to pending settlement', function () {
    $cashAdvance = CashAdvance::factory()->create([
        'status' => CashAdvanceStatus::Approved, // Assuming Approved is a valid pre-settlement state
    ]);

    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Draft,
        'cash_advance_id' => $cashAdvance->id,
    ]);

    $this->action->execute($expenseReport, $this->user);

    expect($expenseReport->refresh()->status)->toBe(ExpenseReportStatus::Submitted)
        ->and($cashAdvance->refresh()->status)->toBe(CashAdvanceStatus::PendingSettlement);
});

it('cannot submit an already submitted expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Submitted,
    ]);

    expect(fn () => $this->action->execute($expenseReport, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Only draft expense reports can be submitted.');
});

it('cannot submit an approved expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Approved,
    ]);

    expect(fn () => $this->action->execute($expenseReport, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Only draft expense reports can be submitted.');
});
