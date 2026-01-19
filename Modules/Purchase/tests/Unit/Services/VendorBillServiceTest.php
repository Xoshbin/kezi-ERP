<?php

namespace Modules\Purchase\tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Modules\Accounting\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use Modules\Accounting\Contracts\VendorBillJournalEntryCreatorContract;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Accounting\Services\BudgetControlService;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Foundation\Services\CurrencyConverterService;
use Modules\Foundation\Services\ExchangeRateService;
use Modules\Foundation\Services\SequenceService;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\ShippingCostAllocationService;
use Modules\Purchase\Services\VendorBillService;
use RuntimeException;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    // Mock Dependencies
    $this->lockDateService = Mockery::mock(LockDateService::class);
    $this->journalEntryService = Mockery::mock(JournalEntryService::class);
    $this->vendorBillJournalEntryCreator = Mockery::mock(VendorBillJournalEntryCreatorContract::class);
    $this->currencyConverter = Mockery::mock(CurrencyConverterService::class);
    $this->exchangeRateService = Mockery::mock(ExchangeRateService::class);
    $this->sequenceService = Mockery::mock(SequenceService::class);
    $this->shippingCostAllocationService = Mockery::mock(ShippingCostAllocationService::class);
    $this->budgetControlService = Mockery::mock(BudgetControlService::class);

    // Mock the Preview Action since it's resolved via app()
    $this->previewAction = Mockery::mock(BuildVendorBillPostingPreviewAction::class);
    $this->app->instance(BuildVendorBillPostingPreviewAction::class, $this->previewAction);

    $this->service = new VendorBillService(
        $this->lockDateService,
        $this->journalEntryService,
        $this->vendorBillJournalEntryCreator,
        $this->currencyConverter,
        $this->exchangeRateService,
        $this->sequenceService,
        $this->shippingCostAllocationService,
        $this->budgetControlService
    );
});

describe('post', function () {
    it('returns early if bill is not draft', function () {
        $bill = VendorBill::factory()->create(['status' => VendorBillStatus::Posted]);

        // No mocks should be called
        $this->lockDateService->shouldNotReceive('enforce');

        $this->service->post($bill, $this->user);

        expect($bill->fresh()->status)->toBe(VendorBillStatus::Posted);
    });

    it('validates lock date, user permissions, and budget before posting', function () {
        $bill = VendorBill::factory()->create([
            'status' => VendorBillStatus::Draft,
            'company_id' => $this->company->id,
            'bill_date' => '2024-01-01',
            'bill_reference' => '', // Empty string to trigger sequence generation
        ]);

        Gate::shouldReceive('forUser->authorize')->with('post', $bill)->once();

        // Expectations
        $this->lockDateService->shouldReceive('enforce')
            ->once()
            ->with(Mockery::on(fn ($c) => $c->id === $this->company->id), Mockery::on(fn ($date) => $date->format('Y-m-d') === '2024-01-01'));

        $this->previewAction->shouldReceive('execute')->andReturn(['errors' => [], 'issues' => []]);

        $this->budgetControlService->shouldReceive('validateVendorBill')->once()->with($bill);

        $this->currencyConverter->shouldReceive('getExchangeRate')->andReturn(1.0);
        $this->currencyConverter->shouldReceive('convertWithRate')->andReturn(Money::of(100, $bill->company->currency->code));

        $this->sequenceService->shouldReceive('getNextVendorBillNumber')->once()->andReturn('BILL-2024-001');

        $journalEntry = JournalEntry::factory()->create();
        $this->vendorBillJournalEntryCreator->shouldReceive('execute')->once()->andReturn($journalEntry);

        // Act
        $this->service->post($bill, $this->user);

        // Assert
        $bill->refresh();
        expect($bill->status)->toBe(VendorBillStatus::Posted)
            ->and($bill->bill_reference)->toBe('BILL-2024-001')
            ->and($bill->journal_entry_id)->toBe($journalEntry->id)
            ->and($bill->user_id)->toBe($this->user->id);
    });

    it('throws exception if validation fails', function () {
        $bill = VendorBill::factory()->create(['status' => VendorBillStatus::Draft]);

        Gate::shouldReceive('forUser->authorize')->with('post', $bill)->once();
        $this->lockDateService->shouldReceive('enforce');

        // Mock validation failure
        $this->previewAction->shouldReceive('execute')->andReturn([
            'errors' => ['Create lines first'],
            'issues' => [['type' => 'no_line_items', 'message' => 'Cannot post without lines']],
        ]);

        expect(fn () => $this->service->post($bill, $this->user))
            ->toThrow(RuntimeException::class, 'Cannot post without lines');
    });

    it('handles duplicate sequence race condition gracefully (theoretical, relies on DB transaction)', function () {
        // Since we are mocking sequence service, strict race condition testing is hard in UNIT tests.
        // But we can verify it requests a sequence if missing.
        $bill = VendorBill::factory()->create([
            'status' => VendorBillStatus::Draft,
            'bill_reference' => '',
        ]);

        Gate::shouldReceive('forUser->authorize');
        $this->lockDateService->shouldReceive('enforce');
        $this->previewAction->shouldReceive('execute')->andReturn(['errors' => []]);
        $this->budgetControlService->shouldReceive('validateVendorBill');
        $this->currencyConverter->shouldReceive('getExchangeRate')->andReturn(1.0);
        $this->currencyConverter->shouldReceive('convertWithRate')->andReturn(Money::of(100, 'USD'));

        $this->sequenceService->shouldReceive('getNextVendorBillNumber')->once()->andReturn('SEQ-123');
        $this->vendorBillJournalEntryCreator->shouldReceive('execute')->andReturn(JournalEntry::factory()->create());

        $this->service->post($bill, $this->user);

        expect($bill->fresh()->bill_reference)->toBe('SEQ-123');
    });
});

describe('delete', function () {
    it('allows deleting draft bills', function () {
        $bill = VendorBill::factory()->create(['status' => VendorBillStatus::Draft]);

        $this->lockDateService->shouldReceive('enforce')->once();

        $result = $this->service->delete($bill);

        expect($result)->toBeTrue()
            ->and(VendorBill::find($bill->id))->toBeNull();
    });

    it('prevents deleting posted bills', function () {
        $bill = VendorBill::factory()->create(['status' => VendorBillStatus::Posted]);

        $this->lockDateService->shouldReceive('enforce')->once();

        expect(fn () => $this->service->delete($bill))
            ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);
    });
});

describe('cancel', function () {
    it('cancels posted bill and creates reversal', function () {
        $journalEntry = JournalEntry::factory()->create();
        $bill = VendorBill::factory()->create([
            'status' => VendorBillStatus::Posted,
            'journal_entry_id' => $journalEntry->id,
        ]);

        Gate::shouldReceive('forUser->authorize')->with('cancel', $bill)->once();

        $this->journalEntryService->shouldReceive('createReversal')
            ->once()
            ->with(
                Mockery::on(fn ($je) => $je->id === $journalEntry->id),
                Mockery::type('string'),
                $this->user
            );

        $this->service->cancel($bill, $this->user, 'Mistake');

        expect($bill->fresh()->status)->toBe(VendorBillStatus::Cancelled);
    });

    it('requires bill to be posted', function () {
        $bill = VendorBill::factory()->create(['status' => VendorBillStatus::Draft]);
        Gate::shouldReceive('forUser->authorize');

        expect(fn () => $this->service->cancel($bill, $this->user, 'test'))
            ->toThrow(Exception::class, 'Only posted vendor bills can be cancelled');
    });
});

describe('resetToDraft', function () {
    it('resets posted bill to draft and reverses journal', function () {
        $journalEntry = JournalEntry::factory()->create();
        $bill = VendorBill::factory()->create([
            'status' => VendorBillStatus::Posted,
            'journal_entry_id' => $journalEntry->id,
            'posted_at' => now(),
            'bill_reference' => 'REF-001',
        ]);

        Gate::shouldReceive('forUser->authorize')->with('resetToDraft', $bill)->once();

        $this->journalEntryService->shouldReceive('createReversal')
            ->once()
            ->with(
                Mockery::on(fn ($je) => $je->id === $journalEntry->id),
                Mockery::pattern('/Reset of Bill REF-001/'),
                $this->user
            );

        $this->service->resetToDraft($bill, $this->user, 'Resetting');

        $bill->refresh();
        expect($bill->status)->toBe(VendorBillStatus::Draft)
            ->and($bill->posted_at)->toBeNull()
            ->and($bill->journal_entry_id)->toBeNull()
            ->and($bill->reset_to_draft_log)->toHaveCount(1)
            ->and($bill->reset_to_draft_log[0]['reason'])->toBe('Resetting');
    });
});

describe('validateShippingCosts', function () {
    it('delegates validation to ShippingCostAllocationService', function () {
        $bill = VendorBill::factory()->create();
        $result = new \Modules\Purchase\DataTransferObjects\Purchases\ShippingCostValidationResult(
            true,
            [],
            [],
            Money::of(0, 'USD')
        );

        $this->shippingCostAllocationService->shouldReceive('validateVendorBillShippingCosts')
            ->once()
            ->with(Mockery::on(fn ($b) => $b->id === $bill->id))
            ->andReturn($result);

        $actual = $this->service->validateShippingCosts($bill);

        expect($actual)->toBe($result);
    });
});
