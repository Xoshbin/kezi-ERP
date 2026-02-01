<?php

namespace Jmeryar\Accounting\Tests\Feature\Currency;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Actions\Currency\PerformCurrencyRevaluationAction;
use Jmeryar\Accounting\DataTransferObjects\Currency\PerformRevaluationDTO;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Currency\RevaluationStatus;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->action = app(PerformCurrencyRevaluationAction::class);
});

test('it creates a currency revaluation in draft status', function () {
    // Arrange
    $company = $this->company;
    $user = $this->user;

    $dto = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today(),
        description: 'Test revaluation',
        auto_post: false,
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation)->not->toBeNull();
    expect($revaluation->company_id)->toBe($company->id);
    expect($revaluation->created_by_user_id)->toBe($user->id);
    expect($revaluation->status)->toBe(RevaluationStatus::Draft);
    expect($revaluation->journal_entry_id)->toBeNull();
    expect($revaluation->posted_at)->toBeNull();
});

test('it generates a unique reference for each revaluation', function () {
    // Arrange
    $company = $this->company;
    $user = $this->user;

    $dto1 = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today(),
    );

    $dto2 = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today()->addDay(),
    );

    // Act
    $revaluation1 = $this->action->execute($dto1);
    $revaluation2 = $this->action->execute($dto2);

    // Assert
    expect($revaluation1->reference)->not->toBeNull();
    expect($revaluation2->reference)->not->toBeNull();
    expect($revaluation1->reference)->not->toBe($revaluation2->reference);
});

test('it creates revaluation lines for foreign currency balances', function () {
    // Arrange
    $company = $this->company;
    $user = $this->user;
    $baseCurrencyCode = $company->currency->code;

    // Create a foreign currency
    $usd = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    // Create exchange rate
    CurrencyRate::factory()->create([
        'currency_id' => $usd->id,
        'company_id' => $company->id,
        'rate' => 1.2,
        'effective_date' => Carbon::today(),
    ]);

    // Create a receivable account
    $receivableAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
    ]);

    // Create a journal with foreign currency transaction
    $journal = Journal::factory()->for($company)->create();
    $entry = JournalEntry::factory()->for($company)->for($journal)->create([
        'entry_date' => Carbon::today()->subDays(30),
        'state' => 'posted',
        'currency_id' => $usd->id,
    ]);

    // Create journal entry line with foreign currency
    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => $receivableAccount->id,
        'debit' => 1000000, // 1000 in minor units
        'credit' => 0,
        'original_currency_id' => $usd->id,
        'original_currency_amount' => 1000000,
        'exchange_rate_at_transaction' => 1.0,
    ]);

    $dto = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today(),
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->lines)->not->toBeEmpty();
});

test('it calculates correct totals for gains and losses', function () {
    // Arrange
    $company = $this->company;
    $user = $this->user;

    $dto = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today(),
    );

    // Act
    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->total_gain)->toBeInstanceOf(Money::class);
    expect($revaluation->total_loss)->toBeInstanceOf(Money::class);
    expect($revaluation->net_adjustment)->toBeInstanceOf(Money::class);
});

test('revaluation can be modified when in draft status', function () {
    // Arrange
    $company = $this->company;
    $user = $this->user;

    $dto = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: Carbon::today(),
    );

    $revaluation = $this->action->execute($dto);

    // Assert
    expect($revaluation->canBeModified())->toBeTrue();
    expect($revaluation->isDraft())->toBeTrue();
});
