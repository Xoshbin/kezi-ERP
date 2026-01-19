<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Enums\Currency\RevaluationStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\CurrencyRevaluation;
use Modules\Accounting\Models\CurrencyRevaluationLine;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Services\Reports\FxGainLossReportService;
use Modules\Foundation\Models\Currency;

beforeEach(function () {
    $this->service = new FxGainLossReportService;

    // Use USD to ensure 2 decimal places for consistent math in tests
    $currency = Currency::factory()->createSafely(['code' => 'USD', 'decimal_places' => 2]);

    $this->company = Company::factory()->create([
        'name' => 'Test Corp',
        'currency_id' => $currency->id,
    ]);

    $this->gainLossAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => '700100',
        'name' => 'FX Gain/Loss',
    ]);

    $this->company->update([
        'default_gain_loss_account_id' => $this->gainLossAccount->id,
    ]);
});

test('it can generate report with realized gains losses', function () {
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    $journal = Journal::factory()->create(['company_id' => $this->company->id]);

    // Create a realized gain (Credit to gain/loss account)
    $entry1 = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'entry_date' => '2024-01-10',
        'state' => JournalEntryState::Posted,
        'reference' => 'SETTLE-001',
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry1->id,
        'company_id' => $this->company->id,
        'account_id' => $this->gainLossAccount->id,
        'debit' => Money::zero('USD'),
        'credit' => Money::ofMinor(10000, 'USD'),
    ]);

    // Create a realized loss (Debit to gain/loss account)
    $entry2 = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'entry_date' => '2024-01-20',
        'state' => JournalEntryState::Posted,
        'reference' => 'SETTLE-002',
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry2->id,
        'company_id' => $this->company->id,
        'account_id' => $this->gainLossAccount->id,
        'debit' => Money::ofMinor(5000, 'USD'),
        'credit' => Money::zero('USD'),
    ]);

    $report = $this->service->generate($this->company, $startDate, $endDate);

    expect($report->realized_gains_losses)->toHaveCount(2)
        ->and($report->total_realized_gain->getMinorAmount()->toInt())->toBe(10000)
        ->and($report->total_realized_loss->getMinorAmount()->toInt())->toBe(5000)
        ->and($report->net_realized->getMinorAmount()->toInt())->toBe(5000);
});

test('it can generate report with unrealized gains losses', function () {
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    $user = User::factory()->create();
    $currency = Currency::factory()->createSafely(['code' => 'EUR', 'decimal_places' => 2]);

    $revaluation = CurrencyRevaluation::factory()->create([
        'company_id' => $this->company->id,
        'revaluation_date' => '2024-01-31',
        'status' => RevaluationStatus::Posted,
        'created_by_user_id' => $user->id,
    ]);

    $account = Account::factory()->create(['company_id' => $this->company->id, 'code' => 'ACC01']);

    CurrencyRevaluationLine::create([
        'currency_revaluation_id' => $revaluation->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'adjustment_amount' => Money::of('25.50', 'USD'),
        'foreign_currency_balance' => Money::of('100.00', 'EUR'),
        'historical_rate' => 1.0,
        'current_rate' => 1.255,
        'book_value' => Money::of('100.00', 'USD'),
        'revalued_amount' => Money::of('125.50', 'USD'),
    ]);

    CurrencyRevaluationLine::create([
        'currency_revaluation_id' => $revaluation->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'adjustment_amount' => Money::of('-10.00', 'USD'),
        'foreign_currency_balance' => Money::of('100.00', 'EUR'),
        'historical_rate' => 1.0,
        'current_rate' => 0.9,
        'book_value' => Money::of('100.00', 'USD'),
        'revalued_amount' => Money::of('90.00', 'USD'),
    ]);

    $report = $this->service->generate($this->company, $startDate, $endDate);

    expect($report->unrealized_gains_losses)->toHaveCount(2)
        ->and($report->total_unrealized_gain->getMinorAmount()->toInt())->toBe(2550)
        ->and($report->total_unrealized_loss->getMinorAmount()->toInt())->toBe(1000)
        ->and($report->net_unrealized->getMinorAmount()->toInt())->toBe(1550);
});

test('it calculates combined totals correctly', function () {
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::factory()->createSafely(['code' => 'GBP', 'decimal_places' => 2]);

    // Realized gain: 100.00
    $entry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'journal_id' => $journal->id,
        'entry_date' => '2024-01-10',
        'state' => JournalEntryState::Posted,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'company_id' => $this->company->id,
        'account_id' => $this->gainLossAccount->id,
        'debit' => Money::zero('USD'),
        'credit' => Money::ofMinor(10000, 'USD'),
    ]);

    // Unrealized loss: -30.00
    $user = User::factory()->create();
    $revaluation = CurrencyRevaluation::factory()->create([
        'company_id' => $this->company->id,
        'revaluation_date' => '2024-01-31',
        'status' => RevaluationStatus::Posted,
        'created_by_user_id' => $user->id,
    ]);
    $account = Account::factory()->create(['company_id' => $this->company->id, 'code' => 'ACC02']);
    CurrencyRevaluationLine::create([
        'currency_revaluation_id' => $revaluation->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'adjustment_amount' => Money::of('-30.00', 'USD'),
        'foreign_currency_balance' => Money::of('100.00', 'GBP'),
        'historical_rate' => 1.0,
        'current_rate' => 0.7,
        'book_value' => Money::of('100.00', 'USD'),
        'revalued_amount' => Money::of('70.00', 'USD'),
    ]);

    $report = $this->service->generate($this->company, $startDate, $endDate);

    expect($report->net_realized->getMinorAmount()->toInt())->toBe(10000)
        ->and($report->net_unrealized->getMinorAmount()->toInt())->toBe(-3000)
        ->and($report->total_net_fx_impact->getMinorAmount()->toInt())->toBe(7000);
});
