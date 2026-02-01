<?php

namespace Kezi\Accounting\Tests\Feature\Currency;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Currency\PerformCurrencyRevaluationAction;
use Kezi\Accounting\DataTransferObjects\Currency\PerformRevaluationDTO;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Currency\RevaluationStatus;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->company->currency->update(['decimal_places' => 3]);
    $this->action = app(PerformCurrencyRevaluationAction::class);
});

test('it throws exception when posting if default gain/loss account is missing', function () {
    // Arrange
    $this->company->update(['default_gain_loss_account_id' => null]);

    $usd = Currency::factory()->createSafely(['code' => 'USD']);
    CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $this->company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    $account = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);
    $journal = Journal::factory()->for($this->company)->create();
    $entry = JournalEntry::factory()->for($this->company)->for($journal)->create([
        'entry_date' => Carbon::today()->subDays(30),
        'state' => 'posted',
        'currency_id' => $usd->id,
    ]);
    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => $account->id,
        'debit' => 100, // 100 IQD
        'original_currency_id' => $usd->id,
        'original_currency_amount' => 100, // 100 USD
        'exchange_rate_at_transaction' => 1.0,
    ]);

    $dto = new PerformRevaluationDTO(
        company_id: $this->company->id,
        created_by_user_id: $this->user->id,
        revaluation_date: Carbon::today(),
        auto_post: true,
    );

    // Act & Assert
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Company must have a default gain/loss account configured.');

    $this->action->execute($dto);
});

test('it throws exception when posting if default bank journal is missing', function () {
    // Arrange
    $gainLossAccount = Account::factory()->for($this->company)->create();
    $this->company->update([
        'default_gain_loss_account_id' => $gainLossAccount->id,
        'default_bank_journal_id' => null,
    ]);

    $usd = Currency::factory()->createSafely(['code' => 'USD']);
    CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $this->company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    $account = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);
    $journal = Journal::factory()->for($this->company)->create();
    $entry = JournalEntry::factory()->for($this->company)->for($journal)->create([
        'entry_date' => Carbon::today()->subDays(30),
        'state' => 'posted',
        'currency_id' => $usd->id,
    ]);
    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => $account->id,
        'debit' => 100,
        'original_currency_id' => $usd->id,
        'original_currency_amount' => 100,
        'exchange_rate_at_transaction' => 1.0,
    ]);

    $dto = new PerformRevaluationDTO(
        company_id: $this->company->id,
        created_by_user_id: $this->user->id,
        revaluation_date: Carbon::today(),
        auto_post: true,
    );

    // Act & Assert
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Company must have a default bank journal configured.');

    $this->action->execute($dto);
});

test('it does not create journal entry if there are no adjustments', function () {
    // Arrange
    $gainLossAccount = Account::factory()->for($this->company)->create();
    $bankJournal = Journal::factory()->for($this->company)->create();
    $this->company->update([
        'default_gain_loss_account_id' => $gainLossAccount->id,
        'default_bank_journal_id' => $bankJournal->id,
    ]);

    // No transactions exist, or exchange rate is exactly the same as historical rate

    $dto = new PerformRevaluationDTO(
        company_id: $this->company->id,
        created_by_user_id: $this->user->id,
        revaluation_date: Carbon::today(),
        auto_post: true,
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->status)->toBe(RevaluationStatus::Draft); // auto_post skipped as no lines
    expect($revaluation->journal_entry_id)->toBeNull();
    expect($revaluation->lines()->count())->toBe(0);
});

test('it handles multiple currencies and balances accurately', function () {
    // Arrange
    $gainLossAccount = Account::factory()->for($this->company)->create();
    $bankJournal = Journal::factory()->for($this->company)->create();
    $this->company->update([
        'default_gain_loss_account_id' => $gainLossAccount->id,
        'default_bank_journal_id' => $bankJournal->id,
    ]);

    $usd = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);
    $eur = Currency::factory()->createSafely(['code' => 'EUR', 'decimal_places' => 2]);

    // Clear any existing rates for these currencies to ensure clean state
    CurrencyRate::where('company_id', $this->company->id)->whereIn('currency_id', [$usd->id, $eur->id])->delete();

    // Rate 1.2 for USD (Gain if asset, as rate was 1.0)
    CurrencyRate::factory()->create(['currency_id' => $usd->id, 'company_id' => $this->company->id, 'rate' => 1.2, 'effective_date' => Carbon::today()]);
    // Rate 0.8 for EUR (Loss if asset, as rate was 1.0)
    CurrencyRate::factory()->create(['currency_id' => $eur->id, 'company_id' => $this->company->id, 'rate' => 0.8, 'effective_date' => Carbon::today()]);

    $account = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);
    $journal = Journal::factory()->for($this->company)->create();

    // USD Transaction: 100.00 USD (original) = 100.000 IQD (book)
    // Casts handle Major Unit conversion
    $entryUsd = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => Carbon::today()->subDays(5), 'state' => 'posted', 'currency_id' => $usd->id]);
    JournalEntryLine::factory()->for($entryUsd)->create(['account_id' => $account->id, 'debit' => 100, 'credit' => 0, 'original_currency_id' => $usd->id, 'original_currency_amount' => 100, 'exchange_rate_at_transaction' => 1.0]);

    // EUR Transaction: 100.00 EUR (original) = 100.000 IQD (book)
    $entryEur = JournalEntry::factory()->for($this->company)->for($journal)->create(['entry_date' => Carbon::today()->subDays(5), 'state' => 'posted', 'currency_id' => $eur->id]);
    JournalEntryLine::factory()->for($entryEur)->create(['account_id' => $account->id, 'debit' => 100, 'credit' => 0, 'original_currency_id' => $eur->id, 'original_currency_amount' => 100, 'exchange_rate_at_transaction' => 1.0]);

    $dto = new PerformRevaluationDTO(
        company_id: $this->company->id,
        created_by_user_id: $this->user->id,
        revaluation_date: Carbon::today(),
        auto_post: true,
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->status)->toBe(RevaluationStatus::Posted);
    expect($revaluation->lines()->count())->toBe(2);

    // Assert
    expect($revaluation->total_gain->getAmount()->toFloat())->toEqualWithDelta(20.0, 0.001);
    expect($revaluation->total_loss->getAmount()->toFloat())->toEqualWithDelta(20.0, 0.001);
    expect($revaluation->net_adjustment->getAmount()->toFloat())->toEqualWithDelta(0.0, 0.001);

    // Check Journal Entry
    $je = $revaluation->journalEntry;
    expect($je)->not->toBeNull();
    // 2 lines for assets, 0 for gain/loss account as net is 0?
    // Wait, let's check PerformCurrencyRevaluationAction.php:183
    // if (! $totalGainLossAdjustment->isZero()) { ... }
    // If net is zero, it should NOT create a line for gain/loss account.
    expect($je->lines()->count())->toBe(2);
});

test('it handles revaluation for specific accounts only', function () {
    // Arrange
    $account1 = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);
    $account2 = Account::factory()->for($this->company)->create(['type' => AccountType::Receivable]);

    $usd = Currency::factory()->createSafely(['code' => 'USD']);
    CurrencyRate::factory()->create(['currency_id' => $usd->id, 'company_id' => $this->company->id, 'rate' => 1.5, 'effective_date' => Carbon::today()]);

    $journal = Journal::factory()->for($this->company)->create();

    // Account 1 has balance
    $entry1 = JournalEntry::factory()->for($this->company)->for($journal)->create(['state' => 'posted', 'currency_id' => $usd->id]);
    JournalEntryLine::factory()->for($entry1)->create(['account_id' => $account1->id, 'debit' => 100, 'original_currency_id' => $usd->id, 'original_currency_amount' => 100, 'exchange_rate_at_transaction' => 1.0]);

    // Account 2 has balance
    $entry2 = JournalEntry::factory()->for($this->company)->for($journal)->create(['state' => 'posted', 'currency_id' => $usd->id]);
    JournalEntryLine::factory()->for($entry2)->create(['account_id' => $account2->id, 'debit' => 100, 'original_currency_id' => $usd->id, 'original_currency_amount' => 100, 'exchange_rate_at_transaction' => 1.0]);

    // Revalue only account 1
    $dto = new PerformRevaluationDTO(
        company_id: $this->company->id,
        created_by_user_id: $this->user->id,
        revaluation_date: Carbon::today(),
        account_ids: [$account1->id],
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->lines()->count())->toBe(1);
    expect($revaluation->lines->first()->account_id)->toBe($account1->id);
});
