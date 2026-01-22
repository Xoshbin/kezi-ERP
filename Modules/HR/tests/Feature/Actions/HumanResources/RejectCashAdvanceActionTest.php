<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Actions\HumanResources\RejectCashAdvanceAction;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Models\CashAdvance;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->approver = User::factory()->create();
    $this->action = app(RejectCashAdvanceAction::class);
});

describe('RejectCashAdvanceAction', function () {
    it('can reject a pending approval cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'notes' => null,
        ]);

        $this->action->execute($cashAdvance, 'Budget constraints', $this->approver);

        expect($cashAdvance->refresh())
            ->status->toBe(CashAdvanceStatus::Rejected);
    });

    it('appends rejection reason to notes', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'notes' => null,
        ]);

        $this->action->execute($cashAdvance, 'Insufficient documentation', $this->approver);

        $cashAdvance->refresh();
        expect($cashAdvance->notes)->toContain('Rejection reason: Insufficient documentation');
    });

    it('preserves existing notes and appends rejection reason', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'notes' => 'Original notes here',
        ]);

        $this->action->execute($cashAdvance, 'Amount too high', $this->approver);

        $cashAdvance->refresh();
        expect($cashAdvance->notes)
            ->toContain('Original notes here')
            ->toContain('Rejection reason: Amount too high');
    });

    it('cannot reject a draft cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Draft,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Some reason', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });

    it('cannot reject an approved cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Too late to reject', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });

    it('cannot reject a disbursed cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Disbursed,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Cannot reject now', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });

    it('cannot reject an already rejected cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Rejected,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Double rejection', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });

    it('cannot reject a settled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Settled,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Already settled', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });

    it('cannot reject a cancelled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Cancelled,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, 'Already cancelled', $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be rejected.');
    });
});
