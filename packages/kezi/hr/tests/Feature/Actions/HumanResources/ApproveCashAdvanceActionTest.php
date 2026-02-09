<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Actions\HumanResources\ApproveCashAdvanceAction;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->approver = User::factory()->create();
    $this->action = app(ApproveCashAdvanceAction::class);
});

describe('ApproveCashAdvanceAction', function () {
    it('can approve a pending approval cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);
        $this->action->execute($cashAdvance, $approvedAmount, $this->approver);

        expect($cashAdvance->refresh())
            ->status->toBe(CashAdvanceStatus::Approved)
            ->approved_at->not->toBeNull()
            ->approved_by_user_id->toBe($this->approver->id);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => CashAdvance::class,
            'auditable_id' => $cashAdvance->id,
            'event_type' => 'cash_advance_approved',
            'company_id' => $this->company->id,
            'user_id' => $this->approver->id,
        ]);
    });

    it('can approve with a lower amount than requested', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(750, $this->company->currency->code);
        $this->action->execute($cashAdvance, $approvedAmount, $this->approver);

        $cashAdvance->refresh();
        expect($cashAdvance->status)->toBe(CashAdvanceStatus::Approved);
        expect($cashAdvance->approved_amount->isEqualTo($approvedAmount))->toBeTrue();
    });

    it('can approve with the exact requested amount', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 500,
        ]);

        $approvedAmount = Money::of(500, $this->company->currency->code);
        $this->action->execute($cashAdvance, $approvedAmount, $this->approver);

        $cashAdvance->refresh();
        expect($cashAdvance->approved_amount->isEqualTo($approvedAmount))->toBeTrue();
    });

    it('sets correct approval metadata', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 1000,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);
        $this->action->execute($cashAdvance, $approvedAmount, $this->approver);

        $cashAdvance->refresh();
        expect($cashAdvance->approved_at)->not->toBeNull();
        expect($cashAdvance->approved_at->diffInSeconds(now()))->toBeLessThan(5);
        expect($cashAdvance->approved_by_user_id)->toBe($this->approver->id);
    });

    it('cannot approve with amount exceeding requested amount', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1500, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Approved amount cannot exceed requested amount.');
    });

    it('cannot approve a draft cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Draft,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });

    it('cannot approve an already approved cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });

    it('cannot approve a disbursed cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Disbursed,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });

    it('cannot approve a settled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Settled,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });

    it('cannot approve a rejected cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Rejected,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });

    it('cannot approve a cancelled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Cancelled,
            'requested_amount' => 1000,
        ]);

        $approvedAmount = Money::of(1000, $this->company->currency->code);

        expect(fn () => $this->action->execute($cashAdvance, $approvedAmount, $this->approver))
            ->toThrow(\InvalidArgumentException::class, 'Only pending cash advances can be approved.');
    });
});
