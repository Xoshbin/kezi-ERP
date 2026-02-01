<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\CreateAdjustmentDocument;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\EditAdjustmentDocument;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Inventory\Actions\Adjustments\CreateAdjustmentDocumentLineAction;
use Jmeryar\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use Jmeryar\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Jmeryar\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Jmeryar\Inventory\Models\AdjustmentDocument;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(AdjustmentDocumentResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(AdjustmentDocumentResource::getUrl('create'))->assertSuccessful();
});

it('can create an adjustment document', function () {
    /** @var Account $account */
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Product Line', // Set a specific name to match the database assertion
        'unit_price' => Money::of(100, $this->company->currency->code), // Set a specific price for predictable total
    ]);

    livewire(CreateAdjustmentDocument::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'currency_id' => $this->company->currency_id,
            'reference_number' => 'Test Adjustment Ref',
            'date' => now()->format('Y-m-d'),
            'type' => AdjustmentDocumentType::CreditNote->value,
            'reason' => 'Test adjustment reason',
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product Line',
                'quantity' => 2,
                'unit_price' => $product->unit_price->getAmount()->toFloat(),
                'account_id' => $account->id,
                'tax_id' => null,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('adjustment_documents', [
        'reference_number' => 'Test Adjustment Ref',
        'status' => AdjustmentDocumentStatus::Draft->value,
    ]);

    $this->assertDatabaseHas('adjustment_document_lines', [
        'product_id' => $product->id,
        'description' => 'Test Product Line',
        'quantity' => 2,
    ]);

    $adjustmentDocument = AdjustmentDocument::first();
    $this->assertEquals(200, $adjustmentDocument->total_amount->getAmount()->toFloat());
});

it('can validate input on create', function () {
    livewire(CreateAdjustmentDocument::class)
        ->fillForm([
            'reference_number' => null,
            'date' => null,
            'type' => null,
            'reason' => null,
            'lines' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'reference_number' => 'required',
            'date' => 'required',
            'type' => 'required',
            'reason' => 'required',
            'lines' => 'min',
        ]);
});

it('can render the edit page', function () {
    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(AdjustmentDocumentResource::getUrl('edit', ['record' => $adjustmentDocument]))
        ->assertSuccessful();
});

it('can edit an adjustment document', function () {
    /** @var Account $account */
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'reference_number' => 'Old Ref',
        'status' => AdjustmentDocumentStatus::Draft,
    ]);

    // Create a line manually to ensure proper setup
    $createLineAction = app(CreateAdjustmentDocumentLineAction::class);
    $createLineAction->execute($adjustmentDocument, new CreateAdjustmentDocumentLineDTO(
        description: 'Test Line',
        quantity: 1,
        unit_price: Money::of(100, $this->company->currency->code),
        account_id: $account->id,
        tax_id: null,
        product_id: null
    ));

    // The mutateFormDataBeforeFill method in EditAdjustmentDocument already handles
    // the conversion of line data with Money objects properly, so we don't need to override it
    livewire(EditAdjustmentDocument::class, [
        'record' => $adjustmentDocument->getRouteKey(),
    ])
        ->fillForm([
            'reference_number' => 'New Ref',
            'reason' => 'Updated reason',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $adjustmentDocument->id,
        'reference_number' => 'New Ref',
        'reason' => 'Updated reason',
    ]);
});

it('can post an adjustment document', function () {
    /** @var Account $account */
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $adjustmentDocument = AdjustmentDocument::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'status' => AdjustmentDocumentStatus::Draft,
    ]);

    // Create a line manually to ensure proper setup
    $createLineAction = app(CreateAdjustmentDocumentLineAction::class);
    $createLineAction->execute($adjustmentDocument, new CreateAdjustmentDocumentLineDTO(
        description: 'Test Line',
        quantity: 1,
        unit_price: Money::of(100, $this->company->currency->code),
        account_id: $account->id,
        tax_id: null,
        product_id: null
    ));

    livewire(EditAdjustmentDocument::class, [
        'record' => $adjustmentDocument->getRouteKey(),
    ])
        ->callAction('post')
        ->assertHasNoErrors();

    $adjustmentDocument->refresh();
    expect($adjustmentDocument->status)->toBe(AdjustmentDocumentStatus::Posted);
});

// Note: resetToDraft action doesn't exist in current implementation
// This test is commented out until the action is implemented
// it('can reset an adjustment document to draft', function () {
//     $adjustmentDocument = AdjustmentDocument::factory()->create([
//         'company_id' => $this->company->id,
//         'status' => AdjustmentDocument::STATUS_POSTED,
//         'posted_at' => now(),
//     ]);

//     livewire(AdjustmentDocumentResource\Pages\EditAdjustmentDocument::class, [
//         'record' => $adjustmentDocument->getRouteKey(),
//     ])
//         ->callAction('resetToDraft', data: [
//             'reason' => 'Test reason',
//         ])
//         ->assertHasNoErrors();

//     $adjustmentDocument->refresh();
//     expect($adjustmentDocument->status)->toBe(AdjustmentDocument::STATUS_DRAFT);
// });
