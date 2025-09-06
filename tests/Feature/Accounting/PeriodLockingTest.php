<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\Actions\Adjustments\CreateAdjustmentDocumentAction;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Purchases\CreateVendorBillAction;
use App\Actions\Sales\CreateInvoiceAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\Enums\Accounting\LockDateType;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Exceptions\PeriodIsLockedException;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Invoice;
use App\Models\LockDate;
use App\Rules\NotInLockedPeriod;
use App\Services\Accounting\LockDateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tests\Traits\MocksTime;
use Tests\Traits\WithConfiguredCompany;

// This file now uses our standardized setup traits for a clean, consistent testing environment.
uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

// We use Pest's `describe` blocks to organize tests into logical groups,
// mirroring the well-structured comments from your original file.

describe('LockDate Service', function () {
    it('returns true for a date within a locked period', function () {
        LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $service = app(LockDateService::class);
        $date = Carbon::parse('2025-12-15');
        expect($service->isPeriodLocked($this->company, $date, LockDateType::HardLock->value))->toBeTrue();
    });

    it('returns false for a date outside a locked period', function () {
        LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $service = app(LockDateService::class);
        $date = Carbon::parse('2026-01-15');
        expect($service->isPeriodLocked($this->company, $date, LockDateType::HardLock->value))->toBeFalse();
    });

    it('throws PeriodIsLockedException for a locked date', function () {
        LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $service = app(LockDateService::class);
        $date = Carbon::parse('2025-12-15');
        $service->enforce($this->company, $date);
    })->throws(PeriodIsLockedException::class);

    it('uses and clears the cache correctly', function () {
        $lockDate = LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::AllUsers,
        ]);
        $service = app(LockDateService::class);
        $date = Carbon::parse('2025-12-15');
        $cacheKey = "lock_date_{$this->company->id}_".LockDateType::AllUsers->value;

        Cache::forget($cacheKey);
        expect(Cache::has($cacheKey))->toBeFalse();

        // First call should cache the result.
        $service->isPeriodLocked($this->company, $date, LockDateType::AllUsers->value);
        expect(Cache::has($cacheKey))->toBeTrue();

        // Update the lock date, which should clear the cache via the observer.
        $lockDate->update(['locked_until' => '2026-01-31']);
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('LockDate Observer', function () {
    it('throws UpdateNotAllowedException when updating a HARD_LOCK LockDate', function () {
        $lockDate = LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $lockDate->update(['locked_until' => '2026-01-01']);
    })->throws(UpdateNotAllowedException::class);

    it('throws UpdateNotAllowedException when deleting a HARD_LOCK LockDate', function () {
        $lockDate = LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $lockDate->delete();
    })->throws(UpdateNotAllowedException::class);
});

describe('Action Integration with Locked Periods', function () {
    // This `beforeEach` applies only to tests inside this `describe` block.
    // It sets up the locked period once for all the action tests.
    beforeEach(function () {
        $this->travelTo('2026-02-01'); // Ensure "now" is outside the locked period
        LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        \App\Models\Partner::factory()->for($this->company)->create();
    });

    it('throws PeriodIsLockedException for CreateInvoiceAction', function () {
        app(CreateInvoiceAction::class)->execute(new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->company->partners->first()->id,
            currency_id: $this->company->currency->id,
            invoice_date: '2025-12-15', // Date is inside locked period
            due_date: '2026-01-14',
            lines: [],
            fiscal_position_id: null
        ));
    })->throws(PeriodIsLockedException::class);

    it('throws PeriodIsLockedException for CreateVendorBillAction', function () {
        app(CreateVendorBillAction::class)->execute(new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $this->company->partners->first()->id,
            currency_id: $this->company->currency->id,
            bill_reference: 'V-123',
            bill_date: '2025-12-15', // Date is inside locked period
            accounting_date: '2025-12-15',
            due_date: null,
            lines: [],
            created_by_user_id: $this->user->id
        ));
    })->throws(PeriodIsLockedException::class);

    it('throws PeriodIsLockedException for CreatePaymentAction', function () {
        $invoice = Invoice::factory()->for($this->company)->create();
        $linkDto = new CreatePaymentDocumentLinkDTO('invoice', $invoice->id, \Brick\Money\Money::of(100, $this->company->currency->code));

        app(CreatePaymentAction::class)->execute(new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->company->default_bank_journal_id,
            currency_id: $this->company->currency->id,
            payment_date: '2025-12-15', // Date is inside locked period
            payment_purpose: PaymentPurpose::Settlement,
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            counterpart_account_id: null,
            document_links: [$linkDto],
            reference: null
        ), $this->user);
    })->throws(PeriodIsLockedException::class);

    it('throws PeriodIsLockedException for CreateJournalEntryAction', function () {
        app(CreateJournalEntryAction::class)->execute(new CreateJournalEntryDTO(
            company_id: $this->company->id,
            journal_id: $this->company->default_sales_journal_id,
            currency_id: $this->company->currency->id,
            entry_date: '2025-12-15', // Date is inside locked period
            reference: 'test',
            description: null,
            created_by_user_id: $this->user->id,
            is_posted: false,
            lines: []
        ));
    })->throws(PeriodIsLockedException::class);

    it('throws PeriodIsLockedException for CreateAdjustmentDocumentAction', function () {
        app(CreateAdjustmentDocumentAction::class)->execute(new CreateAdjustmentDocumentDTO(
            company_id: $this->company->id,
            type: AdjustmentDocumentType::CreditNote,
            date: '2025-12-15', // Date is inside locked period
            reference_number: 'ADJ-123',
            reason: 'test',
            currency_id: $this->company->currency->id,
            original_invoice_id: null,
            original_vendor_bill_id: null,
            lines: []
        ));
    })->throws(PeriodIsLockedException::class);
});

describe('Validation Rule (NotInLockedPeriod)', function () {
    beforeEach(function () {
        LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
    });

    it('fails validation when a date is inside a locked period', function () {
        $rule = new NotInLockedPeriod($this->company);
        $validator = Validator::make(['date' => '2025-12-15'], ['date' => $rule]);
        expect($validator->fails())->toBeTrue();
    });

    it('passes validation when a date is outside a locked period', function () {
        $rule = new NotInLockedPeriod($this->company);
        $validator = Validator::make(['date' => '2026-01-15'], ['date' => $rule]);
        expect($validator->passes())->toBeTrue();
    });
});

describe('Filament Resource (LockDateResource)', function () {
    it('disables edit and delete actions for HardLock records', function () {
        $hardLock = LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2025-12-31',
            'lock_type' => LockDateType::HardLock,
        ]);
        $softLock = LockDate::factory()->create([
            'company_id' => $this->company->id,
            'locked_until' => '2026-01-31',
            'lock_type' => LockDateType::AllUsers,
        ]);

        \Livewire\Livewire::test(\App\Filament\Clusters\Settings\Resources\LockDates\Pages\ListLockDates::class, [
            'record' => $this->company,
        ])
            ->assertTableActionDisabled('edit', $hardLock)
            ->assertTableActionDisabled('delete', $hardLock)
            ->assertTableActionEnabled('edit', $softLock)
            ->assertTableActionEnabled('delete', $softLock);
    });
});
