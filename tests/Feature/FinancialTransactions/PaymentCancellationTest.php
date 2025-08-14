<?php

use App\Models\User;
use App\Models\Payment;
use Tests\Traits\MocksTime;
use App\Services\PaymentService;
use App\Enums\Payments\PaymentStatus;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Accounting\JournalEntryState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('cancelling a confirmed payment creates a reversing journal entry and an audit log', function () {

    // Create and confirm a payment in the company's currency
    $payment = Payment::factory()->for($this->company)->create([
        'status' => 'draft',
        'currency_id' => $this->company->currency_id,
        'amount' => \Brick\Money\Money::of(1000, $this->company->currency->code),
    ]);
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
        'description' => 'Payment Cancelled: ' . $cancellationReason,
    ]);
});
