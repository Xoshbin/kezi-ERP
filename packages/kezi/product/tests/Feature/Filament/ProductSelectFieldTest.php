<?php

namespace Kezi\Product\Tests\Feature\Filament;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Kezi\Foundation\Models\Currency;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;
use Kezi\Product\Models\Product;
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

it('can extract schema from ProductResource', function () {
    $component = ProductSelectField::make('product_id');

    $mockHasForms = new class extends Component implements HasForms
    {
        use InteractsWithForms;

        public function render()
        {
            return '<div></div>';
        }
    };

    $schema = Schema::make($mockHasForms);

    $components = $component->getCreateOptionActionForm($schema);

    expect($components)->toBeArray()
        ->and($components)->not->toBeEmpty();

    $fieldNames = [];
    $collectNames = function (array $components) use (&$fieldNames, &$collectNames) {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $name = $component->getName()) {
                $fieldNames[] = $name;
            }
            if (method_exists($component, 'getChildComponents')) {
                $collectNames($component->getChildComponents());
            }
        }
    };
    $collectNames($components);

    expect($fieldNames)->toContain('name')
        ->toContain('sku')
        ->toContain('type');
});

it('can create a product using the component logic', function () {
    $component = ProductSelectField::make('product_id');

    $createOptionUsing = $component->getCreateOptionUsing();
    expect($createOptionUsing)->toBeCallable();

    $data = [
        'name' => 'New Test Product',
        'sku' => 'TEST-002',
        'type' => \Kezi\Product\Enums\Products\ProductType::Service->value,
        'company_id' => $this->company->id,
    ];

    $id = $createOptionUsing($data);

    expect($id)->toBeInt();
    $this->assertDatabaseHas('products', [
        'id' => $id,
        'company_id' => $this->company->id,
    ]);

    $product = Product::find($id);
    expect($product->name)->toBe('New Test Product');
});

it('does not crash when opening create product modal from a non-product context (regression: RFQ)', function () {
    // Regression test for: BadMethodCallException
    // "Call to undefined method Kezi\Purchase\Models\RequestForQuotation::incomeAccount()"
    //
    // The bug: TranslatableSelect::getRelatedModelClass() resolved the Livewire context
    // record as RequestForQuotation (outer page model) and blindly called relationship
    // methods like incomeAccount() on it when the Product create modal was opened.
    //
    // The fix: added a method_exists() guard + getModelInstance() fallback so it resolves
    // from the schema container chain (which correctly points to Product in this context).
    $usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);
    $this->company->update(['currency_id' => $usd->id]);

    $this->actingAs($this->user);

    expect(fn () => Livewire::test(CreateRequestForQuotation::class)
        ->callAction('lines.0.product_id.createOption')
    )->not->toThrow(\BadMethodCallException::class);
});
