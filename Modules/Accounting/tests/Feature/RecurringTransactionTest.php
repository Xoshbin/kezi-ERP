<?php

namespace Modules\Accounting\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\RecurringFrequency;
use Modules\Accounting\Enums\Accounting\RecurringStatus;
use Modules\Accounting\Enums\Accounting\RecurringTargetType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\RecurringTemplate;
use Modules\Foundation\Models\Currency;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Journal $journal;

    protected Account $accountDebit;

    protected Account $accountCredit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        $this->currency = Currency::factory()->create(['code' => 'USD']);
        $this->journal = Journal::factory()->create(['company_id' => $this->company->id]);

        $this->accountDebit = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '100100',
            'name' => 'Debit Account',
            'currency_id' => $this->currency->id,
        ]);

        $this->accountCredit = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '200100',
            'name' => 'Credit Account',
            'currency_id' => $this->currency->id,
        ]);
    }

    public function test_it_creates_journal_entry_from_recurring_template()
    {
        $template = RecurringTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Monthly Rent',
            'frequency' => RecurringFrequency::Monthly,
            'interval' => 1,
            'start_date' => Carbon::today(),
            'next_run_date' => Carbon::today(),
            'status' => RecurringStatus::Active,
            'target_type' => RecurringTargetType::JournalEntry,
            'template_data' => [
                'journal_id' => $this->journal->id,
                'currency_id' => $this->currency->id,
                'description' => 'Monthly Rent Payment',
                'lines' => [
                    [
                        'account_id' => $this->accountDebit->id,
                        'debit' => 1000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $this->accountCredit->id,
                        'debit' => 0,
                        'credit' => 1000,
                    ],
                ],
            ],
            'created_by_user_id' => $this->user->id,
        ]);

        $this->artisan('accounting:process-recurring')
            ->expectsOutput('Starting recurring transactions processing...')
            ->expectsOutput("Processing template: {$template->name} (ID: {$template->id})")
            ->assertExitCode(0);

        // Assert Journal Entry created
        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $this->company->id,
            'journal_id' => $this->journal->id,
            'description' => 'Monthly Rent Payment',
            'is_posted' => 0, // Should be draft
        ]);

        // Assert Next Run Date updated
        $template->refresh();
        $this->assertEquals(Carbon::today()->addMonth()->format('Y-m-d'), $template->next_run_date->format('Y-m-d'));
    }

    public function test_it_skips_future_transactions()
    {
        $template = RecurringTemplate::factory()->create([
            'company_id' => $this->company->id,
            'next_run_date' => Carbon::tomorrow(),
            'status' => RecurringStatus::Active,
            'target_type' => RecurringTargetType::JournalEntry,
        ]);

        $this->artisan('accounting:process-recurring')
            ->expectsOutput('Found 0 templates due for processing.')
            ->assertExitCode(0);

        // Assert No Journal Entry created (need to verify more specifically if needed, but output count covers it)
    }
}
