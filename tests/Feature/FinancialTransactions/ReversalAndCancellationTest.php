<?php

namespace Tests\Feature\FinancialTransactions;

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use Tests\Traits\MocksTime;
use App\Models\JournalEntry;
use App\Services\PaymentService;
use App\Enums\Payments\PaymentStatus;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;
use App\Services\JournalEntryService;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Accounting\JournalEntryState;
use App\Exceptions\DeletionNotAllowedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

beforeEach(function () {
    $this->journalEntryService = app(JournalEntryService::class);
    $this->paymentService = app(PaymentService::class);
});

// Test Suite for JournalEntryService->createReversal()
describe('Journal Entry Reversals', function () {

    test('it successfully creates a reversing journal entry for a posted entry', function () {
        // Arrange: Create an original posted journal entry.
        $currencyCode = $this->company->currency->code;
        $originalEntry = JournalEntry::factory()->for($this->company)->create([
            'is_posted' => true,
            'total_debit' => Money::of(150, $currencyCode),
            'total_credit' => Money::of(150, $currencyCode),
        ]);
        $arAccount = $this->company->defaultAccountsReceivable;
        $revenueAccount = Account::factory()->for($this->company)->create(['type' => 'income']);

        $originalEntry->lines()->createMany([
            ['account_id' => $arAccount->id, 'debit' => Money::of(150, $currencyCode), 'credit' => Money::of(0, $currencyCode)],
            ['account_id' => $revenueAccount->id, 'credit' => Money::of(150, $currencyCode), 'debit' => Money::of(0, $currencyCode)],
        ]);

        // Act: Create the reversal.
        $reversingEntry = $this->journalEntryService->createReversal($originalEntry, 'Test Reversal', $this->user);

        // Assert: Check the new reversing entry.
        $this->assertModelExists($reversingEntry);
        expect($reversingEntry->is_posted)->toBeTrue();
        expect($reversingEntry->reference)->toBe('REV/' . $originalEntry->reference);
        expect($reversingEntry->total_debit->isEqualTo(Money::of(150, $currencyCode)))->toBeTrue();
        expect($reversingEntry->total_credit->isEqualTo(Money::of(150, $currencyCode)))->toBeTrue();

        // Assert: Check that the lines are the exact inverse of the original.
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $reversingEntry->id,
            'account_id' => $arAccount->id,
            'debit' => 0,
            'credit' => 150000, // Stored as minor units
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $reversingEntry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 150000,
            'credit' => 0,
        ]);

        // Assert: Check that the original entry is now marked as reversed.
        $originalEntry->refresh();
        expect($originalEntry->state)->toBe(JournalEntryState::Reversed);
        expect($originalEntry->reversed_entry_id)->toBe($reversingEntry->id);
    });

    test('it prevents reversing a draft journal entry', function () {
        // Arrange: Create a draft journal entry.
        $draftEntry = JournalEntry::factory()->for($this->company)->create(['is_posted' => false]);

        // Act & Assert: Expect an exception when trying to reverse it.
        expect(fn() => $this->journalEntryService->createReversal($draftEntry, 'Should fail', $this->user))
            ->toThrow(\Exception::class, 'Only posted journal entries can be reversed.');
    });
});

// Test Suite for PaymentService->cancel()
describe('Payment Cancellations', function () {

    test('it successfully cancels a confirmed payment', function () {
        // Arrange: Create a posted invoice for the payment to be applied to.
        $invoice = Invoice::factory()->for($this->company)->create([
            'status' => 'posted',
            'total_amount' => \Brick\Money\Money::of(250, $this->company->currency->code),
        ]);

        // Arrange: Create a draft payment using the proper Action, which is the official way.
        $linkDto = new \App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO('invoice', $invoice->id, Money::of(250, $this->company->currency->code));
        $paymentDto = new \App\DataTransferObjects\Payments\CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->company->default_bank_journal_id,
            currency_id: $this->company->currency_id,
            payment_date: now()->toDateString(),
            document_links: [$linkDto],
            reference: 'Test Payment'
        );
        $payment = (app(\App\Actions\Payments\CreatePaymentAction::class))->execute($paymentDto, $this->user);

        // Act 1: Confirm the payment.
        $this->paymentService->confirm($payment, $this->user);
        $payment->refresh();
        expect($payment->status)->toBe(PaymentStatus::Confirmed);
        $originalEntryId = $payment->journal_entry_id;
        expect($originalEntryId)->not->toBeNull();

        // Act 2: Cancel the now-confirmed payment.
        $this->paymentService->cancel($payment, $this->user, 'Test cancellation reason.');

        // Assert: Check that the payment and its original entry are correctly cancelled/reversed.
        $payment->refresh();
        expect($payment->status)->toBe(PaymentStatus::Canceled);
        $this->assertDatabaseHas('journal_entries', [
            'id' => $originalEntryId,
            'state' => 'reversed',
        ]);
        $this->assertDatabaseCount('journal_entries', 2);
    });

    test('it prevents cancelling a draft payment', function () {
        // Arrange: Create a draft payment.
        $draftPayment = Payment::factory()->for($this->company)->create(['status' => PaymentStatus::Draft]);

        // Act & Assert: Expect an exception.
        expect(fn() => $this->paymentService->cancel($draftPayment, $this->user, 'Should fail')) // FIX
        ->toThrow(\Exception::class, 'Only confirmed payments can be cancelled.');
    });

    test('it prevents cancelling a reconciled payment', function () {
        // Arrange: Create a reconciled payment.
        $reconciledPayment = Payment::factory()->for($this->company)->create(['status' => PaymentStatus::Reconciled]);

        // Act & Assert: Expect an exception.
        expect(fn() => $this->paymentService->cancel($reconciledPayment, $this->user, 'Should fail')) // FIX
        ->toThrow(\Exception::class, 'Only confirmed payments can be cancelled.');
    });
});
