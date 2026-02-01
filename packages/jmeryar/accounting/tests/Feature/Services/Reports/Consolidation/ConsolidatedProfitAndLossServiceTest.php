<?php

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Jmeryar\Accounting\Services\Reports\Consolidation\ConsolidatedProfitAndLossService;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\CurrencyRate;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Currencies
    $usd = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);

    /** @var Company $parent */
    $parent = Company::factory()->create([
        'name' => 'Parent Corp',
        'currency_id' => $usd->id,
        'consolidation_method' => \Jmeryar\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
    ]);
    $this->parent = $parent;

    /** @var Company $child */
    $child = Company::factory()->create([
        'name' => 'Child Ltd',
        'parent_company_id' => $parent->id,
        'currency_id' => $eur->id,
        'consolidation_method' => \Jmeryar\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
    ]);
    $this->child = $child;

    // Setup Exchange Rate (1 EUR = 1.1 USD)
    CurrencyRate::create([
        'currency_id' => $eur->id,
        'rate' => 1.100000,
        'effective_date' => Carbon::now()->subDay(),
        'company_id' => $parent->id,
    ]);
});

test('it generates consolidated p and l with multi company data and translation', function () {
    /** @var Company $parent */
    $parent = $this->parent;
    /** @var Company $child */
    $child = $this->child;

    $date = Carbon::now();

    // 1. Parent Income (USD)
    /** @var Account $parentRevenue */
    $parentRevenue = Account::factory()->create([
        'company_id' => $parent->id,
        'code' => '4000',
        'name' => 'Sales USD',
        'type' => AccountType::Income,
    ]);
    /** @var Account $parentCash */
    $parentCash = Account::factory()->create(['company_id' => $parent->id, 'type' => AccountType::BankAndCash]);

    createJournalEntry($parent, $date, [
        ['account_id' => $parentCash->id, 'debit' => 500, 'credit' => 0],
        ['account_id' => $parentRevenue->id, 'debit' => 0, 'credit' => 500],
    ]);

    // 2. Child Income (EUR)
    /** @var Account $childRevenue */
    $childRevenue = Account::factory()->create([
        'company_id' => $child->id,
        'code' => '4000', // Same code for consolidation mapping
        'name' => 'Sales EUR',
        'type' => AccountType::Income,
    ]);
    /** @var Account $childCash */
    $childCash = Account::factory()->create(['company_id' => $child->id, 'type' => AccountType::BankAndCash]);

    // 200 EUR * 1.1 = 220 USD
    createJournalEntry($child, $date, [
        ['account_id' => $childCash->id, 'debit' => 200, 'credit' => 0],
        ['account_id' => $childRevenue->id, 'debit' => 0, 'credit' => 200],
    ]);

    // 3. Child Expense (EUR)
    /** @var Account $childExpense */
    $childExpense = Account::factory()->create([
        'company_id' => $child->id,
        'code' => '6000',
        'name' => 'Maintenance EUR',
        'type' => AccountType::Expense,
    ]);

    // 50 EUR * 1.1 = 55 USD
    createJournalEntry($child, $date, [
        ['account_id' => $childExpense->id, 'debit' => 50, 'credit' => 0],
        ['account_id' => $childCash->id, 'debit' => 0, 'credit' => 50],
    ]);

    // 4. Run Service
    /** @var ConsolidatedProfitAndLossService $service */
    $service = app(ConsolidatedProfitAndLossService::class);
    $dto = $service->generate($parent, $date);

    // 5. Assertions
    // Total Income: 500 (USD) + (200 EUR * 1.1) = 500 + 220 = 720 USD
    expect($dto->totalIncome->getAmount()->toFloat())->toEqual(720.00);

    // Total Expense: 55 USD
    expect($dto->totalExpenses->getAmount()->toFloat())->toEqual(55.00);

    // Net Profit: 720 - 55 = 665 USD
    expect($dto->netProfit->getAmount()->toFloat())->toEqual(665.00);

    // Check mapping
    $revenueLine = $dto->incomeLines->firstWhere('accountCode', '4000');
    expect($revenueLine)->not->toBeNull();
    /** @var \Jmeryar\Accounting\DataTransferObjects\Reports\ReportLineDTO $revenueLine */
    expect($revenueLine->balance->getAmount()->toFloat())->toEqual(720.00);

    $expenseLine = $dto->expenseLines->firstWhere('accountCode', '6000');
    expect($expenseLine)->not->toBeNull();
    /** @var \Jmeryar\Accounting\DataTransferObjects\Reports\ReportLineDTO $expenseLine */
    expect($expenseLine->balance->getAmount()->toFloat())->toEqual(55.00);
});

/**
 * @param  array<int, array<string, mixed>>  $lines
 */
function createJournalEntry(Company $company, Carbon $date, array $lines): void
{
    $je = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'entry_date' => $date->format('Y-m-d'),
        'state' => 'posted',
        'is_posted' => true,
    ]);

    foreach ($lines as $line) {
        JournalEntryLine::factory()->create([
            'company_id' => $company->id,
            'journal_entry_id' => $je->id,
            'account_id' => $line['account_id'],
            'debit' => $line['debit'],
            'credit' => $line['credit'],
        ]);
    }
}
