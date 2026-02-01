<?php

namespace Jmeryar\HR\Tests\Feature\Actions\HumanResources;

use App\Models\User;
use Jmeryar\HR\Actions\HumanResources\ApproveExpenseReportAction;
use Jmeryar\HR\Enums\ExpenseReportStatus;
use Jmeryar\HR\Models\ExpenseReport;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(ApproveExpenseReportAction::class);
});

it('can approve a submitted expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Submitted,
    ]);

    $this->action->execute($expenseReport, $this->user);

    expect($expenseReport->refresh())
        ->status->toBe(ExpenseReportStatus::Approved)
        ->approved_at->not->toBeNull()
        ->approved_by_user_id->toBe($this->user->id);
});

it('cannot approve a draft expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Draft,
    ]);

    expect(fn () => $this->action->execute($expenseReport, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Only submitted expense reports can be approved.');
});

it('cannot approve an already approved expense report', function () {
    $expenseReport = ExpenseReport::factory()->create([
        'status' => ExpenseReportStatus::Approved,
    ]);

    expect(fn () => $this->action->execute($expenseReport, $this->user))
        ->toThrow(\InvalidArgumentException::class, 'Only submitted expense reports can be approved.');
});
