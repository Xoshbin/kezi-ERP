<?php

namespace Kezi\Foundation\Tests\Feature\Filament\Forms\Components;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Filament\Forms\Components\PartnerSelectField;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Livewire\Component;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    \Filament\Facades\Filament::setCurrentPanel(
        \Filament\Facades\Filament::getPanel('kezi')
    );
});

it('can extract schema from PartnerResource', function () {
    $component = PartnerSelectField::make('partner_id');

    $mockHasForms = new class extends Component implements HasForms
    {
        use InteractsWithForms;

        public function render(): string
        {
            return '<div></div>';
        }
    };

    $schema = Schema::make($mockHasForms);

    $components = $component->getCreateOptionActionForm($schema);

    expect($components)->toBeArray()
        ->and($components)->not->toBeEmpty();

    $fieldNames = [];
    $collectNames = function (array $components) use (&$fieldNames, &$collectNames): void {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $name = $component->getName()) {
                $fieldNames[] = $name;
            }
            if (method_exists($component, 'getChildComponents')) {
                $children = $component->getChildComponents();
                $collectNames($children);
            }
        }
    };
    $collectNames($components);

    expect($fieldNames)->toContain('name')
        ->toContain('email')
        ->toContain('phone')
        ->toContain('type')
        ->toContain('receivable_account_id')
        ->toContain('payable_account_id');
});

it('can create a partner using the component logic', function () {
    $component = PartnerSelectField::make('partner_id');

    /** @var \Closure $createOptionUsing */
    $createOptionUsing = $component->getCreateOptionUsing();
    expect($createOptionUsing)->toBeCallable();

    $data = [
        'name' => 'New Test Partner',
        'email' => 'partner@example.com',
        'type' => PartnerType::Vendor->value,
        'company_id' => $this->company->id,
    ];

    $id = $createOptionUsing($data);

    expect($id)->toBeInt();
    $this->assertDatabaseHas('partners', [
        'id' => $id,
        'company_id' => $this->company->id,
        'email' => 'partner@example.com',
    ]);

    /** @var Partner $partner */
    $partner = Partner::find($id);
    expect($partner->name)->toBe('New Test Partner');
});

it('does not crash when opening create partner modal from a non-partner context (regression: RFQ)', function () {
    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);
    $this->company->update(['currency_id' => $usd->id]);

    $this->actingAs($this->user);

    // Call the create action for vendor_id on RFQ create page
    expect(fn () => Livewire::test(CreateRequestForQuotation::class)
        ->callAction('vendor_id.createOption')
    )->not->toThrow(\BadMethodCallException::class);
});
