<?php

namespace Jmeryar\Payment\Tests\Feature\FinancialTransactions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Payment\Enums\Payments\PaymentStatus;
use Jmeryar\Payment\Models\Payment;
use Jmeryar\Payment\Services\PaymentService;
use Tests\Traits\WithConfiguredCompany;

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
    expect(fn () => $this->paymentService->delete($payment))
        ->toThrow(\Jmeryar\Foundation\Exceptions\DeletionNotAllowedException::class);

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
