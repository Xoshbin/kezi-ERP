<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Events\AdjustmentDocumentPosted;
use Modules\Inventory\Models\AdjustmentDocument;
use Tests\Traits\WithConfiguredCompany;

// Import the Money class

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('an adjustment document can be posted, which creates a journal entry and dispatches an event', function () {
    // Arrange: Ensure events are being listened for.
    Event::fake();

    // Arrange: Set up the necessary journal.
    $adjustmentJournal = Journal::factory()->for($this->company)->create(['type' => JournalType::Miscellaneous]);
    $this->company->update(['default_adjustment_journal_id' => $adjustmentJournal->id]);

    // Arrange: Create a draft adjustment document with total amounts.
    // MODIFIED: Create amounts using Money objects.
    $currencyCode = $this->company->currency->code;
    $document = AdjustmentDocument::factory()->for($this->company)->create([
        'status' => AdjustmentDocumentStatus::Draft,
        'currency_id' => $this->company->currency_id,
        'total_amount' => Money::of(200, $currencyCode),
        'total_tax' => Money::of(0, $currencyCode),
    ]);

    // Act: Post the document using the service.
    (app(AdjustmentDocumentService::class))->post($document, $this->user);

    // Assert: The document's status is now 'posted'.
    $this->assertDatabaseHas('adjustment_documents', [
        'id' => $document->id,
        'status' => AdjustmentDocumentStatus::Posted->value,
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
