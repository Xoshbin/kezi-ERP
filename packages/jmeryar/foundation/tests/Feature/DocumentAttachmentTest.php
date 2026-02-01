<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jmeryar\Accounting\Models\Asset;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Foundation\Models\DocumentAttachment;
use Jmeryar\Purchase\Models\PurchaseOrder;
use Jmeryar\Sales\Models\Invoice;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    Storage::fake('local');
});

test('it can create document attachment for invoice', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');
    $filePath = $file->store('document-attachments/invoices', 'local');

    $attachment = DocumentAttachment::create([
        'company_id' => $this->company->id,
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'file_name' => 'invoice.pdf',
        'file_path' => $filePath,
        'file_size' => 100 * 1024,
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $this->assertDatabaseHas('document_attachments', [
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'file_name' => 'invoice.pdf',
    ]);

    expect($attachment->attachable)->toBeInstanceOf(Invoice::class);
    expect($attachment->attachable->id)->toBe($invoice->id);
});

test('it can create document attachment for purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $file = UploadedFile::fake()->create('po-confirmation.pdf', 150, 'application/pdf');
    $filePath = $file->store('document-attachments/purchase-orders', 'local');

    $attachment = DocumentAttachment::create([
        'company_id' => $this->company->id,
        'attachable_type' => PurchaseOrder::class,
        'attachable_id' => $purchaseOrder->id,
        'file_name' => 'po-confirmation.pdf',
        'file_path' => $filePath,
        'file_size' => 150 * 1024,
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $this->assertDatabaseHas('document_attachments', [
        'attachable_type' => PurchaseOrder::class,
        'attachable_id' => $purchaseOrder->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(PurchaseOrder::class);
});

test('it can create document attachment for journal entry', function () {
    $journalEntry = JournalEntry::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $file = UploadedFile::fake()->create('supporting-doc.pdf', 200, 'application/pdf');
    $filePath = $file->store('document-attachments/journal-entries', 'local');

    $attachment = DocumentAttachment::create([
        'company_id' => $this->company->id,
        'attachable_type' => JournalEntry::class,
        'attachable_id' => $journalEntry->id,
        'file_name' => 'supporting-doc.pdf',
        'file_path' => $filePath,
        'file_size' => 200 * 1024,
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $this->assertDatabaseHas('document_attachments', [
        'attachable_type' => JournalEntry::class,
        'attachable_id' => $journalEntry->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(JournalEntry::class);
});

test('it can create document attachment for asset', function () {
    $asset = Asset::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $file = UploadedFile::fake()->create('purchase-receipt.pdf', 120, 'application/pdf');
    $filePath = $file->store('document-attachments/assets', 'local');

    $attachment = DocumentAttachment::create([
        'company_id' => $this->company->id,
        'attachable_type' => Asset::class,
        'attachable_id' => $asset->id,
        'file_name' => 'purchase-receipt.pdf',
        'file_path' => $filePath,
        'file_size' => 120 * 1024,
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $this->assertDatabaseHas('document_attachments', [
        'attachable_type' => Asset::class,
        'attachable_id' => $asset->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(Asset::class);
});

test('it can access attachments relationship from invoice', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
    ]);

    DocumentAttachment::factory()->count(3)->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $invoice->refresh();

    expect($invoice->attachments)->toHaveCount(3);
    expect($invoice->attachments->first())->toBeInstanceOf(DocumentAttachment::class);
});

test('it can access attachments relationship from purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create([
        'company_id' => $this->company->id,
    ]);

    DocumentAttachment::factory()->count(2)->create([
        'attachable_type' => PurchaseOrder::class,
        'attachable_id' => $purchaseOrder->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
    ]);

    $purchaseOrder->refresh();

    expect($purchaseOrder->attachments)->toHaveCount(2);
});

test('it formats file size correctly', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $attachment = DocumentAttachment::factory()->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
        'file_size' => 1024 * 1024, // 1MB
    ]);

    expect($attachment->formatted_file_size)->toBe('1 MB');
});

test('it can check if file exists', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $file = UploadedFile::fake()->create('test-invoice.pdf', 100, 'application/pdf');
    $filePath = $file->store('document-attachments/invoices', 'local');

    $attachment = DocumentAttachment::factory()->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
        'file_path' => $filePath,
    ]);

    expect($attachment->fileExists())->toBeTrue();
});

test('it deletes file when attachment is deleted', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $file = UploadedFile::fake()->create('test-invoice.pdf', 100, 'application/pdf');
    $filePath = $file->store('document-attachments/invoices', 'local');

    $attachment = DocumentAttachment::factory()->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
        'file_path' => $filePath,
    ]);

    // Verify file exists
    expect(Storage::disk('local')->exists($filePath))->toBeTrue();

    // Delete attachment
    $attachment->delete();

    // Verify file is deleted
    expect(Storage::disk('local')->exists($filePath))->toBeFalse();
});

test('it belongs to company', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $attachment = DocumentAttachment::factory()->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
    ]);

    expect($attachment->company)->toBeInstanceOf(Company::class);
    expect($attachment->company->id)->toBe($this->company->id);
});

test('it belongs to uploaded by user', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $attachment = DocumentAttachment::factory()->create([
        'attachable_type' => Invoice::class,
        'attachable_id' => $invoice->id,
        'company_id' => $this->company->id,
        'uploaded_by_user_id' => $this->user->id,
    ]);

    expect($attachment->uploadedBy)->toBeInstanceOf(User::class);
    expect($attachment->uploadedBy->id)->toBe($this->user->id);
});

test('it supports multiple file types', function () {
    $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

    $fileTypes = [
        ['name' => 'document.pdf', 'mime' => 'application/pdf'],
        ['name' => 'image.jpg', 'mime' => 'image/jpeg'],
        ['name' => 'spreadsheet.xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];

    foreach ($fileTypes as $fileType) {
        $attachment = DocumentAttachment::factory()->create([
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'company_id' => $this->company->id,
            'uploaded_by_user_id' => $this->user->id,
            'file_name' => $fileType['name'],
            'mime_type' => $fileType['mime'],
        ]);

        expect($attachment->mime_type)->toBe($fileType['mime']);
    }

    expect($invoice->attachments)->toHaveCount(3);
});
