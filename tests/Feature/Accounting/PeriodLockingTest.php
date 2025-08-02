<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\Actions\Adjustments\CreateAdjustmentDocumentAction;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\Enums\Accounting\LockDateType;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Filament\Resources\LockDateResource;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\LockDate;
use App\Models\Payment;
use App\Models\User;
use App\Models\VendorBill;
use App\Rules\NotInLockedPeriod;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use Tests\Traits\WithUnlockedPeriod;

uses(RefreshDatabase::class, WithUnlockedPeriod::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);

    $currency = \App\Models\Currency::factory()->create();
    $partner = \App\Models\Partner::factory()->create(['company_id' => $this->company->id]);
    $journal = \App\Models\Journal::factory()->create(['company_id' => $this->company->id]);
    Invoice::factory()->create(['company_id' => $this->company->id, 'customer_id' => $partner->id, 'currency_id' => $currency->id]);
});

//-////////////////////////////////////////////////////////////////////////////////////////////////
// LockDateService Tests
//-////////////////////////////////////////////////////////////////////////////////////////////////

it('returns true for a date within a locked period', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $service = app(LockDateService::class);
    $date = Carbon::parse('2023-12-15');

    expect($service->isPeriodLocked($this->company, $date, LockDateType::HARD_LOCK->value))->toBeTrue();
});

it('returns false for a date outside a locked period', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $service = app(LockDateService::class);
    $date = Carbon::parse('2024-01-15');

    expect($service->isPeriodLocked($this->company, $date, LockDateType::HARD_LOCK->value))->toBeFalse();
});

it('throws PeriodIsLockedException for a locked date', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $service = app(LockDateService::class);
    $date = Carbon::parse('2023-12-15');

    $this->expectException(PeriodIsLockedException::class);

    $service->enforce($this->company, $date);
});

it('does not throw an exception for an unlocked date', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $service = app(LockDateService::class);
    $date = Carbon::parse('2024-01-15');

    $service->enforce($this->company, $date);

    $this->assertTrue(true); // No exception thrown
});

it('uses and clears the cache correctly', function () {
    // Arrange
    $lockDate = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::ALL_USERS, // Use a soft lock to allow updates
    ]);
    $service = app(LockDateService::class);
    $date = Carbon::parse('2023-12-15');
    $cacheKey = "lock_date_{$this->company->id}_" . LockDateType::ALL_USERS->value;

    // Clear cache just in case it's dirty from other tests
    Cache::forget($cacheKey);
    expect(Cache::has($cacheKey))->toBeFalse();

    // Act: First call, should cache the result.
    $service->isPeriodLocked($this->company, $date, LockDateType::ALL_USERS->value);

    // Assert: The result should now be in the cache.
    expect(Cache::has($cacheKey))->toBeTrue();

    // Act: Second call, should hit the cache (we can't directly test this without a spy, but we've proven it's cached).
    $service->isPeriodLocked($this->company, $date, LockDateType::ALL_USERS->value);

    // Act: Update the lock date, which should clear the cache.
    $lockDate->update(['locked_until' => '2024-01-31']);

    // Assert: The cache should be cleared by the observer.
    expect(Cache::has($cacheKey))->toBeFalse();

    // Act: Third call, should cache again.
    $service->isPeriodLocked($this->company, $date, LockDateType::ALL_USERS->value);

    // Assert: The result should be back in the cache.
    expect(Cache::has($cacheKey))->toBeTrue();
});


//-////////////////////////////////////////////////////////////////////////////////////////////////
// Observer Tests (LockDateObserver)
//-////////////////////////////////////////////////////////////////////////////////////////////////

it('throws UpdateNotAllowedException when updating a HARD_LOCK LockDate', function () {
    $lockDate = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(UpdateNotAllowedException::class);

    $lockDate->update(['locked_until' => '2024-01-01']);
    $this->expectException(UpdateNotAllowedException::class);

    $lockDate->update(['locked_until' => '2024-01-01']);
    $this->assertTrue(true);
});

it('throws UpdateNotAllowedException when deleting a HARD_LOCK LockDate', function () {
    $lockDate = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(UpdateNotAllowedException::class);

    $lockDate->delete();
});

it('clears the cache when a LockDate is saved or deleted', function () {
    Cache::spy();

    $lockDate = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::ALL_USERS,
    ]);

    Cache::shouldHaveReceived('forget')->once();

    $lockDate->update(['locked_until' => '2024-01-31']);

    Cache::shouldHaveReceived('forget')->twice();

    $lockDate->delete();

    Cache::shouldHaveReceived('forget')->times(3);
});


//-////////////////////////////////////////////////////////////////////////////////////////////////
// Action Integration Tests
//-////////////////////////////////////////////////////////////////////////////////////////////////

it('throws PeriodIsLockedException for CreateInvoiceAction', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(PeriodIsLockedException::class);

    app(CreateInvoiceAction::class)->execute(new CreateInvoiceDTO(
        company_id: $this->company->id,
        customer_id: $this->company->partners->first()->id,
        currency_id: $this->company->currency->id,
        invoice_date: '2023-12-15',
        due_date: '2024-01-14',
        lines: [],
        fiscal_position_id: null
    ));
});

it('throws PeriodIsLockedException for CreateVendorBillAction', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(PeriodIsLockedException::class);

    app(CreateVendorBillAction::class)->execute(new CreateVendorBillDTO(
        company_id: $this->company->id,
        vendor_id: $this->company->partners->first()->id,
        currency_id: $this->company->currency->id,
        bill_reference: 'V-123',
        bill_date: '2023-12-15',
        accounting_date: '2023-12-15',
        due_date: null,
        lines: []
    ));
});

it('throws PeriodIsLockedException for CreatePaymentAction', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(PeriodIsLockedException::class);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->company->partners->first()->id,
        'currency_id' => $this->company->currency->id,
        'total_amount' => Money::of(100, $this->company->currency->code),
    ]);

    $linkDto = new \App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO('invoice', $invoice->id, '100');

    app(CreatePaymentAction::class)->execute(new CreatePaymentDTO(
        company_id: $this->company->id,
        journal_id: $this->company->journals->first()->id,
        currency_id: $this->company->currency->id,
        payment_date: '2023-12-15',
        document_links: [$linkDto],
        reference: null
    ), $this->user);
});

it('throws PeriodIsLockedException for CreateJournalEntryAction', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(PeriodIsLockedException::class);

    app(CreateJournalEntryAction::class)->execute(new CreateJournalEntryDTO(
        company_id: $this->company->id,
        journal_id: $this->company->journals->first()->id,
        currency_id: $this->company->currency->id,
        entry_date: '2023-12-15',
        reference: 'test',
        description: null,
        created_by_user_id: $this->user->id,
        is_posted: false,
        lines: []
    ));
});

it('throws PeriodIsLockedException for CreateAdjustmentDocumentAction', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $this->expectException(PeriodIsLockedException::class);

    app(CreateAdjustmentDocumentAction::class)->execute(new CreateAdjustmentDocumentDTO(
        company_id: $this->company->id,
        type: 'credit',
        date: '2023-12-15',
        reference_number: 'ADJ-123',
        reason: 'test',
        currency_id: $this->company->currency->id,
        original_invoice_id: null,
        original_vendor_bill_id: null,
        lines: []
    ));
});


//-////////////////////////////////////////////////////////////////////////////////////////////////
// Validation Rule Tests (NotInLockedPeriod)
//-////////////////////////////////////////////////////////////////////////////////////////////////

it('fails validation when a date is inside a locked period', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $rule = new NotInLockedPeriod($this->company);

    $validator = Validator::make(
        ['date' => '2023-12-15'],
        ['date' => $rule]
    );

    expect($validator->fails())->toBeTrue();
});

it('passes validation when a date is outside a locked period', function () {
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $rule = new NotInLockedPeriod($this->company);

    $validator = Validator::make(
        ['date' => '2024-01-15'],
        ['date' => $rule]
    );

    expect($validator->passes())->toBeTrue();
});


//-////////////////////////////////////////////////////////////////////////////////////////////////
// Filament Resource Tests (LockDateResource)
//-////////////////////////////////////////////////////////////////////////////////////////////////

it('disables edit and delete actions for HARD_LOCK records in LockDateResource', function () {
    $hardLock = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
        'lock_type' => LockDateType::HARD_LOCK,
    ]);

    $softLock = LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2024-01-31',
        'lock_type' => LockDateType::ALL_USERS,
    ]);

    \Livewire\Livewire::test(LockDateResource\Pages\ListLockDates::class)
        ->assertTableActionDisabled('edit', $hardLock)
        ->assertTableActionDisabled('delete', $hardLock)
        ->assertTableActionEnabled('edit', $softLock)
        ->assertTableActionEnabled('delete', $softLock);
});
