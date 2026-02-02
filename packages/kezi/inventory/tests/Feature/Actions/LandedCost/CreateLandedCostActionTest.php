<?php

namespace Kezi\Inventory\Tests\Feature\Actions\LandedCost;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Inventory\Actions\LandedCost\CreateLandedCostAction;
use Kezi\Inventory\DataTransferObjects\LandedCost\LandedCostData;
use Kezi\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Kezi\Inventory\Enums\Inventory\LandedCostStatus;
use Kezi\Inventory\Models\LandedCost;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(CreateLandedCostAction::class);
});

it('creates a landed cost with valid data', function () {
    $date = now();
    $amountTotal = Money::of(500, $this->company->currency->code);

    $dto = new LandedCostData(
        company: $this->company,
        date: $date,
        amount_total: $amountTotal,
        allocation_method: LandedCostAllocationMethod::ByQuantity,
        description: 'Test Creation',
        created_by_user: $this->user,
        status: LandedCostStatus::Draft
    );

    $landedCost = $this->action->execute($dto);

    expect($landedCost)->toBeInstanceOf(LandedCost::class)
        ->and($landedCost->company_id)->toBe($this->company->id)
        ->and($landedCost->date->isSameDay($date))->toBeTrue()
        ->and($landedCost->amount_total->isEqualTo($amountTotal))->toBeTrue()
        ->and($landedCost->allocation_method)->toBe(LandedCostAllocationMethod::ByQuantity)
        ->and($landedCost->description)->toBe('Test Creation')
        ->and($landedCost->created_by_user_id)->toBe($this->user->id)
        ->and($landedCost->status)->toBe(LandedCostStatus::Draft);

    $this->assertDatabaseHas('landed_costs', [
        'id' => $landedCost->id,
        'company_id' => $this->company->id,
        'description' => 'Test Creation',
        'status' => LandedCostStatus::Draft->value,
    ]);
});

it('creates a landed cost associated with a vendor bill', function () {
    $vendorBill = \Kezi\Purchase\Models\VendorBill::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $dto = new LandedCostData(
        company: $this->company,
        date: now(),
        amount_total: Money::of(250, $this->company->currency->code),
        allocation_method: LandedCostAllocationMethod::ByCost,
        vendor_bill_id: $vendorBill->id,
    );

    $landedCost = $this->action->execute($dto);

    expect($landedCost->vendor_bill_id)->toBe($vendorBill->id);

    $this->assertDatabaseHas('landed_costs', [
        'id' => $landedCost->id,
        'vendor_bill_id' => $vendorBill->id,
    ]);
});
