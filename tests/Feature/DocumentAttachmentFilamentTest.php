<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\DocumentAttachment;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\TestCase;

class DocumentAttachmentFilamentTest extends TestCase
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

        // Authenticate and set tenant
        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }

    /** @test */
    public function it_can_create_invoice_with_attachments_through_filament()
    {
        $customer = Partner::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::factory()->create();

        $file1 = UploadedFile::fake()->create('invoice-copy.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg');

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'attachments' => [$file1, $file2],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify invoice was created
        $invoice = Invoice::latest()->first();
        expect($invoice)->not->toBeNull();

        // Verify attachments were created
        expect($invoice->attachments)->toHaveCount(2);
        expect($invoice->attachments->first()->file_name)->toContain('.pdf');
        expect($invoice->attachments->last()->file_name)->toContain('.jpg');

        // Verify files exist in storage
        expect($invoice->attachments->first()->fileExists())->toBeTrue();
        expect($invoice->attachments->last()->fileExists())->toBeTrue();
    }

    /** @test */
    public function it_can_upload_attachments_to_existing_draft_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $file = UploadedFile::fake()->create('supporting-doc.pdf', 200, 'application/pdf');

        Livewire::test(EditInvoice::class, ['record' => $invoice->id])
            ->fillForm([
                'attachments' => [$file],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $invoice->refresh();
        expect($invoice->attachments)->toHaveCount(1);
        expect($invoice->attachments->first()->file_name)->toContain('.pdf');
    }

    /** @test */
    public function it_prevents_attachment_upload_on_posted_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
        ]);

        Livewire::test(EditInvoice::class, ['record' => $invoice->id])
            ->assertFormFieldIsDisabled('attachments');
    }

    /** @test */
    public function it_can_delete_attachments_from_draft_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $filePath = $file->store('document-attachments/invoices', 'local');

        $attachment = DocumentAttachment::create([
            'company_id' => $this->company->id,
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'file_name' => 'test.pdf',
            'file_path' => $filePath,
            'file_size' => 100 * 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $this->user->id,
        ]);

        expect(Storage::disk('local')->exists($filePath))->toBeTrue();

        // Delete attachment
        $attachment->delete();

        // Verify file was deleted from storage
        expect(Storage::disk('local')->exists($filePath))->toBeFalse();

        // Verify attachment was deleted from database
        expect($invoice->fresh()->attachments)->toHaveCount(0);
    }

    /** @test */
    public function it_prevents_attachment_deletion_from_posted_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
        ]);

        DocumentAttachment::factory()->create([
            'company_id' => $this->company->id,
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'uploaded_by_user_id' => $this->user->id,
        ]);

        Livewire::test(EditInvoice::class, ['record' => $invoice->id])
            ->assertFormFieldIsDisabled('attachments');
    }

    /** @test */
    public function it_displays_existing_attachments_in_edit_form()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
        ]);

        DocumentAttachment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'uploaded_by_user_id' => $this->user->id,
        ]);

        $component = Livewire::test(EditInvoice::class, ['record' => $invoice->id]);

        // Verify the component loads
        $component->assertSuccessful();

        // Verify attachments count
        expect($invoice->attachments)->toHaveCount(3);
    }

    /** @test */
    public function it_supports_multiple_file_types_in_filament_upload()
    {
        $customer = Partner::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::factory()->create();

        $fileTypes = [
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('image.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('sheet.xlsx', 75, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'attachments' => $fileTypes,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = Invoice::latest()->first();
        expect($invoice->attachments)->toHaveCount(3);

        // Verify different MIME types
        $mimeTypes = $invoice->attachments->pluck('mime_type')->toArray();
        expect($mimeTypes)->toContain('application/pdf');
        expect($mimeTypes)->toContain('image/jpeg');
        expect($mimeTypes)->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function it_validates_max_file_size()
    {
        $customer = Partner::factory()->create(['company_id' => $this->company->id]);
        $currency = Currency::factory()->create();

        // Create a file larger than 10MB (10240KB)
        $largeFile = UploadedFile::fake()->create('large-file.pdf', 15000, 'application/pdf');

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'attachments' => [$largeFile],
            ])
            ->call('create')
            ->assertHasFormErrors(['attachments']);
    }

    /** @test */
    public function it_stores_uploader_information()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $filePath = $file->store('document-attachments/invoices', 'local');

        $attachment = DocumentAttachment::create([
            'company_id' => $this->company->id,
            'attachable_type' => Invoice::class,
            'attachable_id' => $invoice->id,
            'file_name' => 'test.pdf',
            'file_path' => $filePath,
            'file_size' => 100 * 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $this->user->id,
        ]);

        expect($attachment->uploadedBy->id)->toBe($this->user->id);
        expect($attachment->uploadedBy->name)->toBe($this->user->name);
    }
}
