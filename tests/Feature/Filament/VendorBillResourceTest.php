<?php

use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(VendorBillResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(VendorBillResource::getUrl('create'))->assertSuccessful();
});

it('can create a vendor bill', function () {
    /** @var \App\Models\Partner $vendor */
    $vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var \App\Models\Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product Line', // Set a specific name to match the database assertion
        'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code), // Set a specific price for predictable total
    ]);



    livewire(\App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'bill_reference' => 'Test Bill Ref',
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product Line',
                'quantity' => 2,
                'unit_price' => $product->unit_price->getAmount()->toFloat(),
                'expense_account_id' => $product->expense_account_id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('vendor_bills', [
        'vendor_id' => $vendor->id,
        'bill_reference' => 'Test Bill Ref',
        'status' => VendorBillStatus::Draft->value,
    ]);

    $this->assertDatabaseHas('vendor_bill_lines', [
        'product_id' => $product->id,
        'description' => 'Test Product Line',
        'quantity' => 2,
    ]);

    $vendorBill = VendorBill::first();
    $this->assertEquals(200, $vendorBill->total_amount->getAmount()->toFloat());
});

it('can validate input on create', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => null,
            'bill_reference' => null,
            'bill_date' => null,
            'accounting_date' => null,
            'lines' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'vendor_id' => 'required',
            'bill_reference' => 'required',
            'bill_date' => 'required',
            'accounting_date' => 'required',
            'lines' => 'min',
        ]);
});

it('can render the edit page', function () {
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(VendorBillResource::getUrl('edit', ['record' => $vendorBill]))
        ->assertSuccessful();
});

it('can edit a vendor bill', function () {
    $vendorBill = VendorBill::factory()->withLines(1)->create([
        'company_id' => $this->company->id,
        'bill_reference' => 'Old Ref',
    ]);

    /** @var \App\Models\Partner $newVendor */
    $newVendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    // The mutateFormDataBeforeFill method in EditVendorBill already handles
    // the conversion of line data with Money objects properly, so we don't need to override it
    livewire(\App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
    ])
        ->fillForm([
            'vendor_id' => $newVendor->id,
            'bill_reference' => 'New Ref',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('vendor_bills', [
        'id' => $vendorBill->id,
        'vendor_id' => $newVendor->id,
        'bill_reference' => 'New Ref',
    ]);
});

it('can confirm a vendor bill', function () {
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'status' => VendorBillStatus::Draft,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill::class, [
        'record' => $vendorBill->getRouteKey(),
    ])
        ->callAction('confirm')
        ->assertHasNoErrors();

    $vendorBill->refresh();
    expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
});

// TODO:: In future if you pland to add back the reset button just enable the test below and enable the action button in the VendorBill resource
/*
 * Temprarily disable resetToDraft button since we are not sure about this feature wheter it's good or no
 * the feature is woking and passing tests */

// it('can reset a vendor bill to draft', function () {
//     $vendorBill = VendorBill::factory()->create([
//         'company_id' => $this->company->id,
//         'status' => VendorBillStatus::Posted,
//         'posted_at' => now(),
//     ]);

//     livewire(VendorBillResource\Pages\EditVendorBill::class, [
//         'record' => $vendorBill->getRouteKey(),
//     ])
//         ->callAction('resetToDraft', data: [
//             'reason' => 'Test reason',
//         ])
//         ->assertHasNoErrors();

//     $vendorBill->refresh();
//     expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
// });
