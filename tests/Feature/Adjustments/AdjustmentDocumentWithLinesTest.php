<?php

namespace Tests\Feature\Adjustments;

use App\Actions\Adjustments\CreateAdjustmentDocumentAction;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('adjustment document totals are calculated correctly from lines', function () {
    // Arrange
    $currencyCode = $this->company->currency->code;
    $adjustmentDoc = AdjustmentDocument::factory()->for($this->company)->create([
        'total_amount' => Money::of(0, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);
    $tax = Tax::factory()->for($this->company)->create(['rate' => 0.10]); // 10% tax
    $account = Account::factory()->for($this->company)->income()->create();

    // Act
    $adjustmentDoc->lines()->create([
        'description' => 'Line 1',
        'quantity' => 2,
        'unit_price' => Money::of(100, $currencyCode),
        'tax_id' => $tax->id,
        'account_id' => $account->id,
    ]);

    $adjustmentDoc->lines()->create([
        'description' => 'Line 2',
        'quantity' => 1,
        'unit_price' => Money::of(50, $currencyCode),
        'tax_id' => null,
        'account_id' => $account->id,
    ]);

    // The model's observer should have calculated and saved the totals.
    $adjustmentDoc->refresh();

    // Assert
    // Line 1: (2 * 100) = 200 subtotal + 20 tax = 220 total
    // Line 2: (1 * 50) = 50 subtotal + 0 tax = 50 total
    // Total Tax: 20
    // Total Amount: 220 + 50 = 270
    expect($adjustmentDoc->total_tax->getAmount()->toFloat())->toBe(20.00);
    expect($adjustmentDoc->total_amount->getAmount()->toFloat())->toBe(270.00);
});


test('create adjustment document action correctly creates document with lines', function () {
    // Arrange
    $currencyCode = $this->company->currency->code;
    $tax = Tax::factory()->for($this->company)->create(['rate' => 0.10]);
    $account = Account::factory()->for($this->company)->income()->create();

    $lineDTOs = [
        new CreateAdjustmentDocumentLineDTO(
            description: 'Service A refund',
            quantity: 1,
            unit_price: '200.00',
            account_id: $account->id,
            tax_id: $tax->id,
            product_id: null
        ),
        new CreateAdjustmentDocumentLineDTO(
            description: 'Shipping fee refund',
            quantity: 1,
            unit_price: '15.00',
            account_id: $account->id,
            tax_id: null,
            product_id: null
        ),
    ];

    $dto = new CreateAdjustmentDocumentDTO(
        company_id: $this->company->id,
        type: AdjustmentDocument::TYPE_CREDIT_NOTE,
        date: now()->toDateString(),
        reference_number: 'CN-TEST-001',
        reason: 'Test creation with lines',
        currency_id: $this->company->currency_id,
        original_invoice_id: null,
        original_vendor_bill_id: null,
        lines: $lineDTOs
    );

    // Act
    $action = new \App\Actions\Adjustments\CreateAdjustmentDocumentAction();
    $adjustmentDoc = $action->execute($dto);

    // Assert
    $this->assertModelExists($adjustmentDoc);
    $this->assertDatabaseCount('adjustment_document_lines', 2);
    expect($adjustmentDoc->lines()->count())->toBe(2);

    // Line 1: 200 subtotal + 20 tax = 220 total
    // Line 2: 15 subtotal + 0 tax = 15 total
    // Total Tax: 20
    // Total Amount: 220 + 15 = 235
    expect($adjustmentDoc->total_tax->getAmount()->toFloat())->toBe(20.00);
    expect($adjustmentDoc->total_amount->getAmount()->toFloat())->toBe(235.00);
});
