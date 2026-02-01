<?php

namespace Jmeryar\Accounting\Tests\Feature\Services\Consolidation;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Accounting\Services\Consolidation\InterCompanyEliminationService;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->baseCurrency = Currency::factory()->create(['code' => 'USD']);

    // Parent Company
    $this->parentCompany = Company::factory()->create([
        'name' => 'Parent Corp',
        'currency_id' => $this->baseCurrency->id,
    ]);

    // Subsidiary Company
    $this->subsidiary = Company::factory()->create([
        'name' => 'Sub Inc',
        'currency_id' => $this->baseCurrency->id, // Simplifed: Same currency for now
    ]);

    $this->service = app(InterCompanyEliminationService::class);
});

test('factory creates correct company', function () {
    $c = Company::factory()->create();
    $je = JournalEntry::factory()->create(['company_id' => $c->id]);
    expect($je->company_id)->toBe($c->id);
    expect($je->journal->company_id)->toBe($c->id);
});

test('it identifies inter-company receivable and payable balances', function () {
    // 1. Setup Inter-Company Partners based on Phase 2 logic
    $subAsPartner = Partner::factory()->create([
        'company_id' => $this->parentCompany->id,
        'linked_company_id' => $this->subsidiary->id,
    ]);

    $parentAsPartner = Partner::factory()->create([
        'company_id' => $this->subsidiary->id,
        'linked_company_id' => $this->parentCompany->id,
    ]);

    // 2. Create Transaction in Parent (Receivable from Sub)
    // Debit IC Receivable 1000, Credit Revenue 1000
    $parentReceivableAccount = Account::factory()->create([
        'company_id' => $this->parentCompany->id,
        'type' => AccountType::Receivable,
    ]);

    // ... helper to create JE ...
    createTestJournalEntry(
        $this->parentCompany,
        $parentReceivableAccount, // Debit
        1000,
        $subAsPartner
    );

    // 3. Create Transaction in Sub (Payable to Parent)
    // Debit Expense 1000, Credit IC Payable 1000
    $subPayableAccount = Account::factory()->create([
        'company_id' => $this->subsidiary->id,
        'type' => AccountType::Payable,
    ]);

    createTestJournalEntry(
        $this->subsidiary,
        $subPayableAccount, // Credit (so pass -1000? or logic)
        -1000,
        $parentAsPartner
    );

    // Debug DB
    // dump(\Jmeryar\Accounting\Models\JournalEntryLine::all()->toArray());
    // dump(\Jmeryar\Accounting\Models\JournalEntry::where('is_posted', true)->count());
    // dump(\Jmeryar\Accounting\Models\JournalEntry::first()->toArray());

    // 4. Run Elimination Service Identify
    $balances = $this->service->identifyInterCompanyBalances(
        [$this->parentCompany->id, $this->subsidiary->id],
        now()->endOfDay() // Ensure we catch the time
    );

    // 5. Assert logic
    expect($balances)->toBeArray();
    expect($balances)->not->toBeEmpty();
    expect($balances)->toHaveCount(2); // One from Parent, one from Sub

    // Verify content
    $first = $balances[0];
    // Check if it's a line
    expect($first)->toBeInstanceOf(\Jmeryar\Accounting\Models\JournalEntryLine::class);
});

/**
 * Helper to create a posted Journal Entry for testing.
 */
function createTestJournalEntry(Company $company, Account $account, float $amount, Partner $partner)
{
    // Create Journal Entry
    $je = JournalEntry::factory()->create([
        'company_id' => $company->id,
        'entry_date' => now(), // Correct column name
        'state' => \Jmeryar\Accounting\Enums\Accounting\JournalEntryState::Posted, // Correct column & Enum
        'is_posted' => true, // Ensure we mark it as posted for query
    ]);

    // Create Line 1 (The Inter-Company Line)
    \Jmeryar\Accounting\Models\JournalEntryLine::factory()->create([
        'journal_entry_id' => $je->id,
        'company_id' => $company->id,
        'account_id' => $account->id,
        'partner_id' => $partner->id,
        'description' => 'Inter-Company Transaction',
        'debit' => Money::of($amount > 0 ? $amount : 0, 'USD'), // Use Money object
        'credit' => Money::of($amount < 0 ? abs($amount) : 0, 'USD'), // Use Money object
        'original_currency_id' => $company->currency_id,
        'original_currency_amount' => Money::of(abs($amount), 'USD'),
    ]);

    // Create Line 2 (Balancing Line - irrelevant for matching but needed for context)
    \Jmeryar\Accounting\Models\JournalEntryLine::factory()->create([
        'journal_entry_id' => $je->id,
        'company_id' => $company->id,
        'account_id' => Account::factory()->create(['company_id' => $company->id]),
        'partner_id' => null,
        'description' => 'Offset',
        'debit' => Money::of($amount < 0 ? abs($amount) : 0, 'USD'),
        'credit' => Money::of($amount > 0 ? $amount : 0, 'USD'),
        'original_currency_id' => $company->currency_id,
        'original_currency_amount' => Money::of(abs($amount), 'USD'),
    ]);
}
