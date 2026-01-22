<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Actions\HumanResources\SubmitCashAdvanceAction;
use Modules\HR\Enums\CashAdvanceStatus;
use Modules\HR\Models\CashAdvance;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();
    $this->action = app(SubmitCashAdvanceAction::class);
});

describe('SubmitCashAdvanceAction', function () {
    it('can submit a draft cash advance for approval', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Draft,
        ]);

        $this->action->execute($cashAdvance, $this->user);

        expect($cashAdvance->refresh())
            ->status->toBe(CashAdvanceStatus::PendingApproval)
            ->requested_at->not->toBeNull();
    });

    it('sets requested_at timestamp when submitting', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Draft,
            'requested_at' => null,
        ]);

        $this->action->execute($cashAdvance, $this->user);

        $cashAdvance->refresh();
        expect($cashAdvance->requested_at)->not->toBeNull();
        expect($cashAdvance->requested_at->diffInSeconds(now()))->toBeLessThan(5);
    });

    it('cannot submit a pending approval cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::PendingApproval,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });

    it('cannot submit an approved cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Approved,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });

    it('cannot submit a disbursed cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Disbursed,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });

    it('cannot submit a settled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Settled,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });

    it('cannot submit a rejected cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Rejected,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });

    it('cannot submit a cancelled cash advance', function () {
        $cashAdvance = CashAdvance::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'status' => CashAdvanceStatus::Cancelled,
        ]);

        expect(fn () => $this->action->execute($cashAdvance, $this->user))
            ->toThrow(\InvalidArgumentException::class, 'Only draft cash advances can be submitted.');
    });
});
