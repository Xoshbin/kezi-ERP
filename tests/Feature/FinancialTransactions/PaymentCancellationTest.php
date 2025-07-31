<?php

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('cancelling a confirmed payment creates a reversing journal entry and an audit log', function () {
    // Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $this->actingAs($user);

    // Create and confirm a payment
    $payment = Payment::factory()->for($company)->create(['status' => 'draft']);
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($payment, $user);
    $payment->refresh();
    
    expect($payment->status)->toBe(Payment::STATUS_CONFIRMED);
    $originalEntry = $payment->journalEntry;

    // Act: Cancel the payment with a specific reason
    $cancellationReason = 'Duplicate payment entry.';
    $paymentService->cancel($payment, $user, $cancellationReason);
    $payment->refresh();
    $originalEntry->refresh();

    // Assert: Payment status and reversal are correct
    expect($payment->status)->toBe(Payment::STATUS_CANCELED);
    expect($originalEntry->state)->toBe('reversed');

    // Assert: Audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Payment::class,
        'auditable_id' => $payment->id,
        'user_id' => $user->id,
        'event_type' => 'cancellation',
        'description' => 'Payment Cancelled: ' . $cancellationReason,
    ]);
});