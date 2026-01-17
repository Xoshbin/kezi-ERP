<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Foundation\Models\AuditLog;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;
use Spatie\Permission\Models\Permission;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
    $this->user = User::factory()->create();
    $this->service = app(InvoiceService::class);
});

test('it can delete a draft invoice', function () {
    /** @var \Tests\TestCase $this */
    /** @var Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $result = $this->service->delete($invoice);

    expect($result)->toBeTrue();
    expect(Invoice::find($invoice->id))->toBeNull();
});

test('it cannot delete a posted invoice', function () {
    /** @var \Tests\TestCase $this */
    /** @var Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
    ]);

    expect(fn () => $this->service->delete($invoice))
        ->toThrow(\Modules\Foundation\Exceptions\DeletionNotAllowedException::class);

    expect(Invoice::find($invoice->id))->not->toBeNull();
});

test('it can reset a posted invoice to draft', function () {
    /** @var \Tests\TestCase $this */
    // Determine the journal entry (mock or create)
    // resetToDraft calls journalEntryService->createReversal

    /** @var JournalEntry $journalEntry */
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
        'journal_entry_id' => $journalEntry->id,
        'invoice_number' => 'INV-001',
        'posted_at' => now(),
    ]);

    // Mock JournalEntryService to avoid complex reversal logic if needed
    // But since it's a feature test, let's see if we can use real service or mock it.
    // Given the complexity of JournalEntryService, mocking it for this test ensures we are testing InvoiceService logic.

    $this->mock(JournalEntryService::class, function ($mock) use ($journalEntry) {
        $mock->shouldReceive('createReversal')
            ->once()
            ->withArgs(fn ($je, $desc, $u) => $je->id === $journalEntry->id)
            ->andReturn(JournalEntry::factory()->create());
    });

    // We need to re-resolve the service to inject the mock
    $service = app(InvoiceService::class);

    $service->resetToDraft($invoice, $this->user, 'Mistake in invoice');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->invoice_number)->toBeNull()
        ->and($invoice->posted_at)->toBeNull()
        ->and($invoice->journal_entry_id)->toBeNull();

    // Verify Audit Log
    /** @var AuditLog|null $log */
    $log = AuditLog::where('auditable_type', Invoice::class)
        ->where('auditable_id', $invoice->id)
        ->where('event_type', 'reset_to_draft')
        ->first();

    expect($log)->not->toBeNull();

    if ($log) {
        expect($log->description)->toContain('Mistake in invoice');
    }
});

test('it can cancel a posted invoice', function () {
    /** @var \Tests\TestCase $this */
    /** @var JournalEntry $journalEntry */
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
    ]);

    /** @var Invoice $invoice */
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
        'journal_entry_id' => $journalEntry->id,
        'invoice_number' => 'INV-002',
    ]);

    // Grant permission
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Permission::firstOrCreate(['name' => 'update_invoice']);
    $this->user->givePermissionTo('update_invoice');

    $this->mock(JournalEntryService::class, function ($mock) use ($journalEntry) {
        $mock->shouldReceive('createReversal')
            ->once()
            ->withArgs(fn ($je, $desc, $u) => $je->id === $journalEntry->id)
            ->andReturn(JournalEntry::factory()->create());
    });

    // Re-resolve service
    $service = app(InvoiceService::class);

    // Mock Gate authorization if needed, but actingAs might suffice if policy allows.
    // InvoiceService::cancel uses Gate::authorize
    // We should probably actAs a user with permission.
    // Assuming default policy allows or we need to mock Gate.
    // Let's assume standard policy checks.
    // But Gate::forUser($user)->authorize('cancel', $invoice) relies on policy.
    // If no policy, it might fail.
    // Let's mock Gate to ensure test isolation.

    // Actually, "uses(WithConfiguredCompany::class)" usually sets up permissions or super admin?
    // Let's rely on Mock to bypass potential policy issues for "unit/service" test.
    // But `Gate::forUser` is static facade.

    // Alternatively, just assume it works or handle it.
    // Let's try running without mocking Gate first, if it fails, we fix.
    // But I'll use `actingAs` just in case, though the service method takes $user explicitly.

    // Wait, the service calls Gate::forUser($user).

    $service->cancel($invoice, $this->user, 'Cancelled due to error');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);

    // Verify Audit Log
    $log = AuditLog::where('auditable_type', Invoice::class)
        ->where('auditable_id', $invoice->id)
        ->where('event_type', 'cancellation')
        ->first();

    expect($log)->not->toBeNull();
});
