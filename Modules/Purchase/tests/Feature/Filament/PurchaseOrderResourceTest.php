<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Product\Models\Product;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product',
        'unit_price' => 1000,
    ]);
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'VAT 10%',
        'rate' => 10,
    ]);
});

describe('PurchaseOrderResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(PurchaseOrderResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(PurchaseOrderResource::getUrl('create', tenant: $this->company))
            ->assertSuccessful();
    });
    it('can create purchase order with line items', function () {
        livewire(CreatePurchaseOrder::class, ['tenant' => $this->company->id])
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->company->currency_id,
                'reference' => 'TEST-REF-001',
                'po_date' => now()->toDateString(),
            ])
            ->set('data.lines', [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product Description',
                    'quantity' => 5,
                    'unit_price' => 10.00,
                    'tax_id' => $this->tax->id,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_orders', [
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'reference' => 'TEST-REF-001',
        ]);

        $this->assertDatabaseHas('purchase_order_lines', [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 10000,
        ]);
    });
});
