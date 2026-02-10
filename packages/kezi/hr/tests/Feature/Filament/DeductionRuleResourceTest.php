<?php

namespace Kezi\HR\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\CreateDeductionRule;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\EditDeductionRule;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\ListDeductionRules;
use Kezi\HR\Models\DeductionRule;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('DeductionRuleResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(DeductionRuleResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(DeductionRuleResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $rule = DeductionRule::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)
            ->get(DeductionRuleResource::getUrl('edit', ['record' => $rule], tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list deduction rules', function () {
        $rules = DeductionRule::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        livewire(ListDeductionRules::class)
            ->assertCanSeeTableRecords($rules);
    });

    it('can create deduction rule', function () {
        $newData = [
            'name' => 'Custom Tax',
            'code' => 'custom_tax',
            'type' => 'percentage',
            'value' => 0.15,
            'is_active' => true,
        ];

        livewire(CreateDeductionRule::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('deduction_rules', [
            'name' => 'Custom Tax',
            'code' => 'custom_tax',
            'company_id' => $this->company->id,
        ]);
    });

    it('can edit deduction rule', function () {
        $rule = DeductionRule::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $newName = 'Updated Rule Name';

        livewire(EditDeductionRule::class, ['record' => $rule->getRouteKey()])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($rule->refresh()->name)->toBe($newName);
    });

    it('can delete deduction rule', function () {
        $rule = DeductionRule::factory()->create([
            'company_id' => $this->company->id,
        ]);

        livewire(ListDeductionRules::class)
            ->callTableAction('delete', $rule);

        $this->assertDatabaseMissing('deduction_rules', [
            'id' => $rule->id,
        ]);
    });
});
