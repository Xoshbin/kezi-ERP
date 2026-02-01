<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Tests\TestCase;

class MultiCurrencyJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $usd;

    protected Currency $iqd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);
        Filament::setTenant($this->company);

        // Add Permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('super_admin');

        // Setup Currencies
        $this->iqd = Currency::firstOrCreate(['code' => 'IQD'], ['name' => 'Iraqi Dinar', 'symbol' => 'IQD']);
        $this->usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$']);

        // Set Company Currency to IQD
        $this->company->currency_id = $this->iqd->id;
        $this->company->save();

        // Create Rate: 1 USD = 1500 IQD
        CurrencyRate::create([
            'company_id' => $this->company->id,
            'currency_id' => $this->usd->id,
            'rate' => 1500,
            'effective_date' => now()->subDay(),
        ]);
    }

    public function test_create_journal_entry_calculates_base_currency_correctly()
    {
        $journal = Journal::factory()->create(['company_id' => $this->company->id]);
        $accountDebit = Account::factory()->create(['company_id' => $this->company->id, 'currency_id' => $this->usd->id]); // Account in USD? Or just generic.
        $accountCredit = Account::factory()->create(['company_id' => $this->company->id]);

        $entryDate = now()->format('Y-m-d');
        $foreignAmount = 100; // 100 USD
        $exchangeRate = 1450; // Custom rate, different from Latest (1500) to prove override works

        // Simulate Form Data submission to CreateJournalEntry page logic
        // We can test the Action directly or the Filament page logic.
        // Testing the Action ensures the core logic is sound, but we modified the Page to handle the conversion.
        // So we should verify via Livewire/Filament test if possible, or just mimic the Page logic invocation.

        // Let's use the actual Action but populate it like the Page does.
        // Or better, creating a test that calls the `CreateJournalEntry` page's methods is hard without Livewire test context.

        // Let's rely on `CreateJournalEntryAction` which creates the entry.
        // Wait, the Page does the conversion BEFORE calling the Action.
        // So I should test that the mapping logic produces the right DTO values.

        // Actually, let's create a functional test that hits the CreateJournalEntry route via Livewire
        // but that might be complex to setup with all the form data.

        // Alternative: Replicate the logic in the test to ensure it produces expected results,
        // then verify the Action handles those results correctly.

        // Let's write a test for the `CreateJournalEntryAction` assuming it receives the PRE-CALCULATED base amounts,
        // which verifies my assumption about `CreateJournalEntryAction` / `JournalEntryLine` behavior.
        // AND create a test for `CreateJournalEntry` page to verify it calculates correctly.

        // verifying Page logic via Livewire is best.

        \Livewire\Livewire::test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry::class)
            ->set('data.journal_id', $journal->id)
            ->set('data.currency_id', $this->usd->id)
            ->set('data.entry_date', $entryDate)
            ->set('data.exchange_rate', $exchangeRate)
            ->set('data.lines', [
                [
                    'account_id' => $accountDebit->id,
                    'debit' => $foreignAmount, // 100 USD input
                    'credit' => 0,
                    'description' => 'Test Debit',
                ],
                [
                    'account_id' => $accountCredit->id,
                    'debit' => 0,
                    'credit' => $foreignAmount, // 100 USD input
                    'description' => 'Test Credit',
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        // Verify Database
        $entry = JournalEntry::latest()->first();
        $this->assertNotNull($entry);

        // Check Header Totals (Base Currency)
        // 100 USD * 1450 = 145,000 IQD
        $expectedBaseAmount = 145000;

        $this->assertEquals($expectedBaseAmount, $entry->total_debit->getAmount()->toInt());
        $this->assertEquals($expectedBaseAmount, $entry->total_credit->getAmount()->toInt());

        // Check Lines
        $this->assertCount(2, $entry->lines);

        $debitLine = $entry->lines->filter(fn ($line) => $line->debit->isGreaterThan(0))->first();
        $this->assertEquals($expectedBaseAmount, $debitLine->debit->getAmount()->toInt());
        $this->assertEquals(100, $debitLine->original_currency_amount->getAmount()->toInt()); // 100 USD
        $this->assertEquals($exchangeRate, $debitLine->exchange_rate_at_transaction);
        $this->assertEquals('USD', $debitLine->originalCurrency->code ?? $debitLine->currency->code); // Should be linked to USD

    }

    public function test_edit_journal_entry_recalculates_base_currency()
    {
        $journal = Journal::factory()->create(['company_id' => $this->company->id]);
        $account = Account::factory()->create(['company_id' => $this->company->id]);

        $initialRate = 1500;
        $initialForeign = 100;
        $initialBase = 150000;

        // Create initial entry directly
        $entry = JournalEntry::factory()->create([
            'company_id' => $this->company->id,
            'journal_id' => $journal->id,
            'currency_id' => $this->usd->id,
            'entry_date' => now(),
        ]);

        // Create lines locally to match structure (Action usually does this)
        // We'll trust the factory/seeder or create manually
        $line1 = \Kezi\Accounting\Models\JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'company_id' => $this->company->id,
            'account_id' => $account->id,
            'debit' => Money::of($initialBase, 'IQD'),
            'credit' => Money::zero('IQD'),
            'original_currency_amount' => Money::of($initialForeign, 'USD'),
            'exchange_rate_at_transaction' => $initialRate,
            'original_currency_id' => $this->usd->id,
            'currency_id' => $this->usd->id,
        ]);

        $line2 = \Kezi\Accounting\Models\JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'company_id' => $this->company->id,
            'account_id' => $account->id,
            'debit' => Money::zero('IQD'),
            'credit' => Money::of($initialBase, 'IQD'),
            'original_currency_amount' => Money::of($initialForeign, 'USD'),
            'exchange_rate_at_transaction' => $initialRate,
            'original_currency_id' => $this->usd->id,
            'currency_id' => $this->usd->id,
        ]);

        $entry->calculateTotalsFromLines();
        $entry->save();

        // Now Edit via Livewire
        // Change Rate to 1600
        $newRate = 1600;

        \Livewire\Livewire::test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages\EditJournalEntry::class, ['record' => $entry->getRouteKey()])
            ->assertSet('data.exchange_rate', 1500) // checks if it loaded correctly
            ->set('data.exchange_rate', $newRate)
            // Assert that the Header Totals (mapped to data.total_debit/total_credit usually? No, let's check the logic)
            // The resource updates `total_debit` and `total_credit` fields in the form data.
            // Let's verify those form fields.
            ->assertSet('data.total_debit', 160000)
            ->assertSet('data.total_credit', 160000)
            ->call('save')
            ->assertHasNoErrors();

        $entry->refresh();
        // UpdateJournalEntryAction recreates lines, so we must fetch fresh lines
        $updatedLine = $entry->lines->first();

        // Expected Base: 100 * 1600 = 160,000 IQD
        $expectedbase = 160000;

        $this->assertEquals($expectedbase, $entry->total_debit->getAmount()->toInt());
        $this->assertEquals($newRate, $updatedLine->exchange_rate_at_transaction);
    }
}
