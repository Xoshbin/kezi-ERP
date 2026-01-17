<?php

namespace Modules\Sales\Tests\Feature\Services;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Contracts\InvoiceJournalEntryCreatorContract;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Events\InvoiceConfirmed;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    // Create necessary accounts and journal for the company
    $journal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
    ]);

    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Receivable,
    ]);

    $this->company->update([
        'default_sales_journal_id' => $journal->id,
        'default_accounts_receivable_id' => $account->id,
    ]);

});

it('delete invoice success', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $this->service = app(InvoiceService::class);
    $result = $this->service->delete($invoice);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});

it('cannot delete posted invoice', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
    ]);

    $this->service = app(InvoiceService::class);
    $this->service->delete($invoice);
})->throws(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);

it('confirm invoice success', function () {
    Event::fake([InvoiceConfirmed::class]);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
        'invoice_date' => Carbon::now()->toDateString(),
    ]);

    // Mock dependencies
    $journalEntry = JournalEntry::factory()->create(['company_id' => $this->company->id]);

    $this->mock(InvoiceJournalEntryCreatorContract::class, function ($mock) use ($journalEntry) {
        $mock->shouldReceive('execute')->once()->andReturn($journalEntry);
    });

    $this->mock(LockDateService::class, function ($mock) {
        $mock->shouldReceive('enforce')->once();
    });

    $this->mock(\Modules\Accounting\Actions\Accounting\BuildInvoicePostingPreviewAction::class, function ($mock) {
        $mock->shouldReceive('execute')->andReturn(['errors' => [], 'issues' => []]);
    });

    $this->service = app(InvoiceService::class);
    $this->service->confirm($invoice, $this->user);

    expect($invoice->status)->toBe(InvoiceStatus::Posted)
        ->and($invoice->posted_at)->not->toBeNull()
        ->and($invoice->journal_entry_id)->toBe($journalEntry->id);

    Event::assertDispatched(InvoiceConfirmed::class);
});

it('reset to draft success', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
        'invoice_date' => Carbon::now()->toDateString(),
        'invoice_number' => 'INV-001',
        'posted_at' => now(),
    ]);

    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
        'is_posted' => true,
    ]);

    // Add lines to satisfy validation
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'debit' => 100,
        'credit' => 0,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'account_id' => Account::factory()->create(['company_id' => $this->company->id])->id,
        'debit' => 0,
        'credit' => 100,
    ]);

    $invoice->journalEntry()->associate($journalEntry);
    $invoice->save();

    $this->mock(JournalEntryService::class, function ($mock) {
        $mock->shouldReceive('createReversal')->once();
    });

    $this->service = app(InvoiceService::class);
    $this->service->resetToDraft($invoice, $this->user, 'Mistake');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->fresh()->invoice_number)->toBeNull()
        ->and($invoice->fresh()->posted_at)->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => get_class($invoice),
        'auditable_id' => $invoice->id,
        'event_type' => 'reset_to_draft',
    ]);
});
