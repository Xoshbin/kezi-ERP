<?php

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Accounting\Services\Reports\Consolidation\ConsolidatedBalanceSheetService;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Models\Partner;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Currencies
    $usd = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
    $eur = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);

    // Setup Companies
    /** @var Company $parent */
    $parent = Company::factory()->create([
        'name' => 'Parent Corp',
        'currency_id' => $usd->id,
        'consolidation_method' => \Kezi\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
    ]);
    $this->parent = $parent;

    /** @var Company $child */
    $child = Company::factory()->create([
        'name' => 'Child Ltd',
        'parent_company_id' => $parent->id,
        'currency_id' => $eur->id,
        'consolidation_method' => \Kezi\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
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

test('it generates consolidated balance sheet with elimination', function () {
    /** @var Company $parent */
    $parent = $this->parent;
    /** @var Company $child */
    $child = $this->child;

    $date = Carbon::now();

    // 1. Setup Inter-company accounts
    /** @var Account $icLoanReceivable */
    $icLoanReceivable = Account::factory()->create([
        'company_id' => $parent->id,
        'code' => '1200',
        'name' => 'IC Loan Rec',
        'type' => AccountType::Receivable,
    ]);

    /** @var Account $icLoanPayable */
    $icLoanPayable = Account::factory()->create([
        'company_id' => $child->id,
        'code' => '2200',
        'name' => 'IC Loan Pay',
        'type' => AccountType::Payable,
    ]);

    // Link Partners for elimination
    /** @var Partner $partnerChild */
    $partnerChild = Partner::factory()->create(['company_id' => $parent->id, 'linked_company_id' => $child->id]);
    /** @var Partner $partnerParent */
    $partnerParent = Partner::factory()->create(['company_id' => $child->id, 'linked_company_id' => $parent->id]);

    // 2. Parent lends 1100 USD to Child
    /** @var Account $parentCash */
    $parentCash = Account::factory()->create(['company_id' => $parent->id, 'type' => AccountType::BankAndCash, 'code' => '1000']);
    createJournalEntryBS($parent, $date, [
        ['account_id' => $icLoanReceivable->id, 'debit' => 1100, 'credit' => 0, 'partner_id' => $partnerChild->id],
        ['account_id' => $parentCash->id, 'debit' => 0, 'credit' => 1100],
    ]);

    // 3. Child records 1000 EUR liability (= 1100 USD)
    /** @var Account $childCash */
    $childCash = Account::factory()->create(['company_id' => $child->id, 'type' => AccountType::BankAndCash, 'code' => '1000']);
    createJournalEntryBS($child, $date, [
        ['account_id' => $childCash->id, 'debit' => 1000, 'credit' => 0],
        ['account_id' => $icLoanPayable->id, 'debit' => 0, 'credit' => 1000, 'partner_id' => $partnerParent->id],
    ]);

    // 4. Run Service
    /** @var ConsolidatedBalanceSheetService $service */
    $service = app(ConsolidatedBalanceSheetService::class);
    $dto = $service->generate($parent, $date);

    // 5. Assertions

    // Check Consolidation: Cash
    // Parent Cash was reduced by 1100. Child Cash was increased by 1000 EUR (= 1100 USD).
    // Net change should be 0 USD.
    $cashLine = $dto->assetLines->firstWhere('accountCode', '1000');
    expect($cashLine)->not->toBeNull();
    /** @var \Kezi\Accounting\DataTransferObjects\Reports\ReportLineDTO $cashLine */
    expect($cashLine->balance->getAmount()->toFloat())->toEqual(0.00);

    // Check Elimination: Loan Receivable
    // Parent: 1100 USD. Should be eliminated.
    $loanRecLine = $dto->assetLines->firstWhere('accountCode', '1200');
    expect($loanRecLine)->not->toBeNull();
    /** @var \Kezi\Accounting\DataTransferObjects\Reports\ReportLineDTO $loanRecLine */
    expect($loanRecLine->balance->getAmount()->toFloat())->toEqual(0.00);

    // Check Elimination: Loan Payable
    // Child: 1000 EUR = 1100 USD. Should be eliminated.
    $loanPayLine = $dto->liabilityLines->firstWhere('accountCode', '2200');
    expect($loanPayLine)->not->toBeNull();
    /** @var \Kezi\Accounting\DataTransferObjects\Reports\ReportLineDTO $loanPayLine */
    expect($loanPayLine->balance->getAmount()->toFloat())->toEqual(0.00);

    // Verify totals balance
    expect($dto->totalAssets->isEqualTo($dto->totalLiabilitiesAndEquity))->toBeTrue();
});

/**
 * @param  array<int, array<string, mixed>>  $lines
 */
function createJournalEntryBS(Company $company, Carbon $date, array $lines): void
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
            'partner_id' => $line['partner_id'] ?? null,
        ]);
    }
}
