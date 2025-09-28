<?php

namespace Modules\Payment\Tests\Feature\FinancialTransactions;

use Modules\Payment\Models\Payment;
use Tests\Traits\WithConfiguredCompany;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Exceptions\DeletionNotAllowedException;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->paymentService = app(PaymentService::class);
});

test('it prevents deletion of a confirmed payment', function () {
    // Arrange: Create a payment and confirm it.
    $payment = Payment::factory()->for($this->company)->create([
        'status' => PaymentStatus::Confirmed,
    ]);

    // Act & Assert: Attempting to delete it should throw our specific exception.
    expect(fn() => $this->paymentService->delete($payment))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);

    // Verify: The payment must still exist in the database.
    $this->assertModelExists($payment);
});

test('it allows deletion of a draft payment', function () {
    // Arrange: Create a draft payment.
    $payment = Payment::factory()->for($this->company)->create([
        'status' => PaymentStatus::Draft,
    ]);

    // Act: Delete the draft payment.
    $this->paymentService->delete($payment);

    // Assert: The payment should be gone from the database.
    $this->assertModelMissing($payment);
});
