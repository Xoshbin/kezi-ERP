<?php

namespace Jmeryar\Accounting\Tests\Feature\Services\Reports\Consolidation;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Services\Reports\Consolidation\ConsolidatedTrialBalanceService;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\CurrencyRate;
use Jmeryar\Foundation\Models\Partner;
use Tests\TestCase;

class ConsolidatedTrialBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $parent;

    protected Company $child;

    protected Currency $usd;

    protected Currency $eur;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Currency
        $this->usd = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
        $this->eur = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);

        // Setup Companies

        $this->parent = Company::factory()->create([
            'name' => 'Parent Corp',
            'currency_id' => $this->usd->id,
            'consolidation_method' => \Jmeryar\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
        ]);

        $this->child = Company::factory()->create([
            'name' => 'Child Ltd',
            'parent_company_id' => $this->parent->id,
            'currency_id' => $this->eur->id,
            'consolidation_method' => \Jmeryar\Accounting\Enums\Consolidation\ConsolidationMethod::Full,
        ]);

        // Setup Exchange Rate (1 EUR = 1.1 USD)
        CurrencyRate::create([
            'currency_id' => $this->eur->id, // From EUR
            // In system, rates are usually "Foreign to Base" or "Base to Foreign".
            // Assuming "rate" is multiplier to get Base (USD)? Or how system handles it?
            // Checking CurrencyTranslationService: convert($amount, $targetCompany...)
            // If EUR -> USD. target is USD (Parent).
            // Rate should be defined.
            // Let's create a rate.
            'rate' => 1.1000,
            'effective_date' => Carbon::now(),
            'company_id' => $this->parent->id, // Rates usually per company or global?
        ]);
    }

    public function test_it_generates_consolidated_trial_balance_with_translation_and_elimination()
    {
        $date = Carbon::now();

        // 1. Create Child Transaction (Revenue) in EUR
        // Account 4000 Revenue
        $revenueAccount = Account::factory()->create([
            'company_id' => $this->child->id,
            'code' => '4000',
            'name' => 'Sales',
            'type' => AccountType::Income,
        ]);

        $receivableAccount = Account::factory()->create([
            'company_id' => $this->child->id,
            'code' => '1100',
            'name' => 'AR',
            'type' => AccountType::Receivable,
        ]);

        // Sale of 100 EUR
        $this->createJournalEntry($this->child, $date, [
            ['account_id' => $receivableAccount->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 100],
        ]);

        // 2. Create Inter-Company Transaction
        // Parent lends 1000 USD to Child.
        // Parent: Dr Loan Receivable (Asset), Cr Cash.
        // Child: Dr Cash, Cr Loan Payable (Liability).

        $loanReceivable = Account::factory()->create([
            'company_id' => $this->parent->id,
            'code' => '1200',
            'name' => 'IC Loan Rec',
            'type' => AccountType::Receivable, // Treat as Receivable for elimination? Or Asset?
            // Elimination service checks Receivable/Payable types.
        ]);

        $loanPayable = Account::factory()->create([
            'company_id' => $this->child->id,
            'code' => '2200',
            'name' => 'IC Loan Pay',
            'type' => AccountType::Payable,
        ]);

        // Link Partners
        $partnerChild = Partner::factory()->create(['company_id' => $this->parent->id, 'linked_company_id' => $this->child->id]);
        $partnerParent = Partner::factory()->create(['company_id' => $this->child->id, 'linked_company_id' => $this->parent->id]);

        // Parent Logic
        $this->createJournalEntry($this->parent, $date, [
            ['account_id' => $loanReceivable->id, 'debit' => 1000, 'credit' => 0, 'partner_id' => $partnerChild->id],
            // Cash account... (simplified, assume cash exists)
        ]);

        // Child Logic (in EUR)
        // 1000 USD = 909.09 EUR approx.
        // Let's use simple numbers.
        // If Rate is 1.1. 100 EUR = 110 USD.
        // Let's say Child records 1000 EUR Loan Payable. = 1100 USD.
        // Parent records 1100 USD Receivable.

        $this->createJournalEntry($this->child, $date, [
            // Cash...
            ['account_id' => $loanPayable->id, 'debit' => 0, 'credit' => 1000, 'partner_id' => $partnerParent->id],
        ]);

        // 3. Run Service
        /** @var ConsolidatedTrialBalanceService $service */
        /** @var ConsolidatedTrialBalanceService $service */
        $service = app(ConsolidatedTrialBalanceService::class);

        $dto = $service->generate($this->parent, $date);

        // 4. Assertions

        // Check Revenue (Child only)
        // 100 EUR * 1.1 = 110 USD Credit.
        $revenueLine = $dto->reportLines->firstWhere('accountCode', '4000');
        expect($revenueLine)->not->toBeNull();
        expect($revenueLine->consolidatedCredit->getAmount()->toFloat())->toEqual(110.00);

        // Check Elimination
        // Parent Rec: 1100 USD (set to match Child's 1000 EUR * 1.1)
        // Child Pay: 1000 EUR * 1.1 = 1100 USD.
        // If I created Parent entry with 1000 USD, and Child with 1000 EUR (1100 USD), elimination is imperfect (100 diff).
        // Elimination Service doesn't balance, it just "identifies".
        // Service sums eliminations.

        // My Logic in Test:
        // Parent: Dr 1000 USD (Code 1200)
        // Child: Cr 1000 EUR (Code 2200)

        // Result:
        // Code 1200: Consolidate Debit = 1000. Elimination Credit = ?
        // Code 2200: Consolidate Credit = 1100. Elimination Debit = ?

        // Elimination Service identifies:
        // Line from Parent (Dr 1000). Elim -> Cr 1000.
        // Line from Child (Cr 1000 EUR = 1100 USD). Elim -> Dr 1100 USD.

        // DTO for 1200:
        // ConsDebit = 1000 + 0 = 1000.
        // ElimCredit = 1000.
        // Net = 0. Correct.

        // DTO for 2200:
        // ConsCredit = 1100.
        // ElimDebit = 1100.
        // Net = 0. Correct.

        $recLine = $dto->reportLines->firstWhere('accountCode', '1200');
        expect($recLine->consolidatedDebit->getAmount()->toFloat())->toEqual(1000.00);
        expect($recLine->eliminationCredit->getAmount()->toFloat())->toEqual(1000.00);

        $payLine = $dto->reportLines->firstWhere('accountCode', '2200');
        expect($payLine->consolidatedCredit->getAmount()->toFloat())->toEqual(1100.00);
        expect($payLine->eliminationDebit->getAmount()->toFloat())->toEqual(1100.00);
    }

    protected function createJournalEntry($company, $date, $lines)
    {
        $je = JournalEntry::factory()->create([
            'company_id' => $company->id,
            'entry_date' => $date->format('Y-m-d'),
            'state' => 'posted', // Must be posted
            'is_posted' => true,
        ]);

        foreach ($lines as $line) {
            \Jmeryar\Accounting\Models\JournalEntryLine::factory()->create([
                'company_id' => $company->id,
                'journal_entry_id' => $je->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'partner_id' => $line['partner_id'] ?? null,
            ]);
        }
    }
}
