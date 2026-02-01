<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Actions\HumanResources\CreateCashAdvanceAction;
use Kezi\HR\DataTransferObjects\HumanResources\CreateCashAdvanceDTO;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\HR\Models\CashAdvance;
use Kezi\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->creatingUser = User::factory()->create();
    $this->action = app(CreateCashAdvanceAction::class);
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
});

describe('CreateCashAdvanceAction', function () {
    it('can create a cash advance in draft status', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Business travel expenses',
            expected_return_date: '2026-02-01',
            notes: 'Traveling to client site',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance)->toBeInstanceOf(CashAdvance::class);
        expect($cashAdvance->company_id)->toBe($this->company->id);
        expect($cashAdvance->employee_id)->toBe($this->employee->id);
        expect($cashAdvance->currency_id)->toBe($this->company->currency_id);
        expect($cashAdvance->status)->toBe(CashAdvanceStatus::Draft);
        expect($cashAdvance->purpose)->toBe('Business travel expenses');
        expect($cashAdvance->notes)->toBe('Traveling to client site');
    });

    it('generates unique advance number', function () {
        $dto1 = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(500, $this->company->currency->code),
            purpose: 'First advance',
        );

        $dto2 = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(750, $this->company->currency->code),
            purpose: 'Second advance',
        );

        $advance1 = $this->action->execute($dto1, $this->creatingUser);
        $advance2 = $this->action->execute($dto2, $this->creatingUser);

        expect($advance1->advance_number)->not->toBeEmpty();
        expect($advance2->advance_number)->not->toBeEmpty();
        expect($advance1->advance_number)->not->toBe($advance2->advance_number);
    });

    it('advance number starts with CA prefix', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Test advance',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->advance_number)->toStartWith('CA');
    });

    it('stores the requested amount correctly', function () {
        $requestedAmount = Money::of(2500, $this->company->currency->code);

        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: $requestedAmount,
            purpose: 'Large expense',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->requested_amount->isEqualTo($requestedAmount))->toBeTrue();
    });

    it('sets expected_return_date when provided', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Short trip',
            expected_return_date: '2026-03-15',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->expected_return_date)->not->toBeNull();
        expect($cashAdvance->expected_return_date->format('Y-m-d'))->toBe('2026-03-15');
    });

    it('can create advance without expected_return_date', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'No return date needed',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->expected_return_date)->toBeNull();
    });

    it('can create advance without notes', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'No notes needed',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->notes)->toBeNull();
    });

    it('initializes approved_amount as null', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Test',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->approved_amount)->toBeNull();
    });

    it('initializes disbursed_amount as null', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Test',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        expect($cashAdvance->disbursed_amount)->toBeNull();
    });

    it('persists the cash advance to database', function () {
        $dto = new CreateCashAdvanceDTO(
            company_id: $this->company->id,
            employee_id: $this->employee->id,
            currency_id: $this->company->currency_id,
            requested_amount: Money::of(1000, $this->company->currency->code),
            purpose: 'Database check',
        );

        $cashAdvance = $this->action->execute($dto, $this->creatingUser);

        $this->assertDatabaseHas('cash_advances', [
            'id' => $cashAdvance->id,
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'purpose' => 'Database check',
        ]);
    });
});
