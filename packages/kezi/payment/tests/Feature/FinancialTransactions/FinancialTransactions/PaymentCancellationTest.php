<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Payment\Enums\Payments\PaymentStatus;
use Kezi\Payment\Models\Payment;
use Kezi\Payment\Services\PaymentService;
use Tests\Traits\MocksTime;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('cancelling a confirmed payment creates a reversing journal entry and an audit log', function () {

    // Create and confirm a payment
    $payment = Payment::factory()->for($this->company)->create(['status' => 'draft']);
    $paymentService = app(PaymentService::class);
    $paymentService->confirm($payment, $this->user);
    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Confirmed);
    $originalEntry = $payment->journalEntry;

    // Act: Cancel the payment with a specific reason
    $cancellationReason = 'Duplicate payment entry.';
    $paymentService->cancel($payment, $this->user, $cancellationReason);
    $payment->refresh();
    $originalEntry->refresh();

    // Assert: Payment status and reversal are correct
    expect($payment->status)->toBe(PaymentStatus::Canceled);
    expect($originalEntry->state)->toBe(JournalEntryState::Reversed);

    // Assert: Audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Payment::class,
        'auditable_id' => $payment->id,
        'user_id' => $this->user->id,
        'event_type' => 'cancellation',
        'description' => 'Payment Cancelled: '.$cancellationReason,
    ]);
});
