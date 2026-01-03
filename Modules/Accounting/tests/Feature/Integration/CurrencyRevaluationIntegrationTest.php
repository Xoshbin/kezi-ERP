<?php

namespace Modules\Accounting\Tests\Feature\Integration;

use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\Actions\Currency\PerformCurrencyRevaluationAction;
use Modules\Accounting\DataTransferObjects\Currency\PerformRevaluationDTO;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\CurrencyRevaluation;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('generates correct journal entry upon revaluation', function () {
    // 1. Setup Data
    $company = $this->company;
    $user = $this->user;
    $today = Carbon::today();
    $baseCurrency = $company->currency;

    // Create Foreign Currency (USD)
    $usd = Currency::factory()->create(['code' => 'USD']);

    // Set OLD Rate (e.g., 1 USD = 1.0 Base) - When transaction happened
    // This is implicitly defined by the transaction's exchange rate

    // Set NEW Rate (e.g., 1 USD = 1.2 Base) - Today's rate for revaluation
    CurrencyRate::factory()->create([
        'company_id' => $company->id,
        'currency_id' => $usd->id,
        'rate' => 1.2,
        'effective_date' => $today,
    ]);

    // Create a Receivable Account (Foreign Currency Aware)
    $receivableAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Receivable,
        'code' => '11000',
        'name' => 'Accounts Receivable (USD)',
    ]);

    // Create an Unrealized Gain/Loss Account
    $gainLossAccount = Account::factory()->for($company)->create([
        'type' => AccountType::Income, // Simplified for test
        'name' => 'Unrealized Exchange Gain/Loss',
    ]);
    // Ideally update company settings to point to this account for revaluation defaults
    // or mock the config service if it's used by the Action.

    // 2. Create Historical Transaction
    // Use a Journal to hold the entry
    $journal = Journal::factory()->for($company)->create(['short_code' => 'GEN']);

    // Create an Invoice-like Journal Entry (posted month ago)
    // Debt of 1000 USD. At rate 1.0, checks out to 1000 Base.
    $entry = JournalEntry::factory()->for($company)->for($journal)->create([
        'state' => 'posted',
        'entry_date' => $today->copy()->subMonth(),
        'description' => 'Original Invoice',
    ]);

    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => $receivableAccount->id,
        'debit' => 100000, // 1000.00 Base
        'credit' => 0,
        'original_currency_id' => $usd->id,
        'original_currency_amount' => 100000, // 1000.00 USD
        'exchange_rate_at_transaction' => 1.0,
    ]);

    // Balancing credit line (Sales) not strictly needed for revaluation logic on specific account
    // but good for completeness if strict validation exists.
    JournalEntryLine::factory()->for($entry)->create([
        'account_id' => Account::factory()->for($company)->create(['type' => AccountType::Income])->id,
        'debit' => 0,
        'credit' => 100000,
    ]);

    // 3. Execute Revaluation
    // At new rate 1.2, 1000 USD = 1200 Base.
    // Old Book Value = 1000 Base.
    // Unrealized Gain = 200 Base.

    $dto = new PerformRevaluationDTO(
        company_id: $company->id,
        created_by_user_id: $user->id,
        revaluation_date: $today,
        description: 'End of month revaluation',
        auto_post: true, // We want to test the full flow including JE creation
    );

    $action = app(PerformCurrencyRevaluationAction::class);
    $revaluation = $action->execute($dto);

    // 4. Assertions
    expect($revaluation)->toBeInstanceOf(CurrencyRevaluation::class);

    // Check Revaluation Object
    // Expected Gain: (1.2 - 1.0) * 1000 = 200
    // Gain is positive adjustment to Asset account.
    $expectedGain = Money::of(200, $baseCurrency->code);

    // Depending on how PerformCurrencyRevaluationAction stores totals:
    // This assumes specific implementation details of the Action.
    // If exact assertion fails, we check logic.
    expect($revaluation->net_adjustment->getAmount()->toFloat())->toBe(20000.00);

    // Check Journal Entry
    $revalEntry = $revaluation->journalEntry;
    expect($revalEntry)->not->toBeNull();
    expect($revalEntry->lines)->toHaveCount(2); // One for Receivables, one for Gain/Loss

    // Find the line for Receivable Account (should be debited to increase value)
    $arLine = $revalEntry->lines->firstWhere('account_id', $receivableAccount->id);
    expect($arLine)->not->toBeNull();
    expect($arLine->debit->getAmount()->toFloat())->toBe(20000.00);

    // Find the line for Gain/Loss Account (should be credited)
    $glLine = $revalEntry->lines->where('account_id', '!=', $receivableAccount->id)->first();
    expect($glLine)->not->toBeNull();
    expect($glLine->credit->getAmount()->toFloat())->toBe(20000.00);
});
