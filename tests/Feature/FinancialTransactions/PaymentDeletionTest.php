<?php

namespace Tests\Feature\FinancialTransactions;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
    $this->paymentService = app(PaymentService::class);
});

test('it prevents deletion of a confirmed payment', function () {
    // Arrange: Create a payment and confirm it.
    $payment = Payment::factory()->for($this->company)->create([
        'status' => Payment::STATUS_CONFIRMED,
    ]);

    // Act & Assert: Attempting to delete it should throw our specific exception.
    expect(fn() => $this->paymentService->delete($payment))
        ->toThrow(DeletionNotAllowedException::class);

    // Verify: The payment must still exist in the database.
    $this->assertModelExists($payment);
});

test('it allows deletion of a draft payment', function () {
    // Arrange: Create a draft payment.
    $payment = Payment::factory()->for($this->company)->create([
        'status' => Payment::STATUS_DRAFT,
    ]);

    // Act: Delete the draft payment.
    $this->paymentService->delete($payment);

    // Assert: The payment should be gone from the database.
    $this->assertModelMissing($payment);
});