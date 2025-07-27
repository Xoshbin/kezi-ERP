<?php

use App\Events\AdjustmentDocumentPosted;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AdjustmentDocumentService;
use Brick\Money\Money; // Import the Money class
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('an adjustment document can be posted, which creates a journal entry and dispatches an event', function () {
    // Arrange: Ensure events are being listened for.
    Event::fake();

    // Arrange: Set up the necessary journal.
    $adjustmentJournal = Journal::factory()->for($this->company)->create(['type' => 'Miscellaneous']);
    $this->company->update(['default_adjustment_journal_id' => $adjustmentJournal->id]);

    // Arrange: Create a draft adjustment document with total amounts.
    // MODIFIED: Create amounts using Money objects.
    $currencyCode = $this->company->currency->code;
    $document = AdjustmentDocument::factory()->for($this->company)->create([
        'status' => AdjustmentDocument::STATUS_DRAFT,
        'total_amount' => Money::of(200, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Post the document using the service.
    (app(AdjustmentDocumentService::class))->post($document, $this->user);

    // Assert: The document's status is now 'posted'.
    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $document->id,
        'status' => AdjustmentDocument::STATUS_POSTED,
    ]);

    // Assert: A journal entry was created and linked to the document.
    $this->assertDatabaseCount('journal_entries', 1);
    $journalEntry = JournalEntry::first();
    expect($document->fresh()->journal_entry_id)->toBe($journalEntry->id);

    // Assert: The journal entry has the correct totals.
    // MODIFIED: Assert against a Money object.
    $expectedAmount = Money::of(200, $currencyCode);
    expect($journalEntry->total_debit->isEqualTo($expectedAmount))->toBeTrue();
    expect($journalEntry->total_credit->isEqualTo($expectedAmount))->toBeTrue();


    // Assert: An event was dispatched.
    Event::assertDispatched(AdjustmentDocumentPosted::class, function ($event) use ($document) {
        return $event->adjustmentDocument->id === $document->id;
    });
});