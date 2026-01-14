<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Accounting\Models\Asset;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\DocumentAttachment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\Invoice;
use Tests\TestCase;

class DocumentAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        // Set up storage for testing
        Storage::fake('local');
    }

    /** @test */
    public function it_can_create_document_attachment_for_invoice()
    {
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
    }

    /** @test */
    public function it_can_create_document_attachment_for_purchase_order()
    {
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
    }

    /** @test */
    public function it_can_create_document_attachment_for_journal_entry()
    {
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
    }

    /** @test */
    public function it_can_create_document_attachment_for_asset()
    {
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
    }

    /** @test */
    public function it_can_access_attachments_relationship_from_invoice()
    {
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
    }

    /** @test */
    public function it_can_access_attachments_relationship_from_purchase_order()
    {
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
    }

    /** @test */
    public function it_formats_file_size_correctly()
    {
        $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

        $attachment = DocumentAttachment::factory()->create([
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'company_id' => $this->company->id,
            'uploaded_by_user_id' => $this->user->id,
            'file_size' => 1024 * 1024, // 1MB
        ]);

        expect($attachment->formatted_file_size)->toBe('1 MB');
    }

    /** @test */
    public function it_can_check_if_file_exists()
    {
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
    }

    /** @test */
    public function it_deletes_file_when_attachment_is_deleted()
    {
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
    }

    /** @test */
    public function it_belongs_to_company()
    {
        $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

        $attachment = DocumentAttachment::factory()->create([
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'company_id' => $this->company->id,
            'uploaded_by_user_id' => $this->user->id,
        ]);

        expect($attachment->company)->toBeInstanceOf(Company::class);
        expect($attachment->company->id)->toBe($this->company->id);
    }

    /** @test */
    public function it_belongs_to_uploaded_by_user()
    {
        $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);

        $attachment = DocumentAttachment::factory()->create([
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'company_id' => $this->company->id,
            'uploaded_by_user_id' => $this->user->id,
        ]);

        expect($attachment->uploadedBy)->toBeInstanceOf(User::class);
        expect($attachment->uploadedBy->id)->toBe($this->user->id);
    }

    /** @test */
    public function it_supports_multiple_file_types()
    {
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
    }
}
