<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\DocumentAttachment;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Storage::fake('local');

    // Create an income account for invoice lines
    $this->incomeAccount = Account::factory()->for($this->company)->create([
        'name' => 'Sales',
        'type' => 'income',
        'code' => '4000',
    ]);
});

it('can create invoice with attachments through filament', function () {
    $customer = Partner::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::factory()->create();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'income_account_id' => $this->incomeAccount->id,
        'unit_price' => 100,
    ]);

    $file1 = UploadedFile::fake()->create('invoice-copy.pdf', 100, 'application/pdf');
    $file2 = UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg');

    // Generate UUID for the line item key
    $uuid = (string) \Illuminate\Support\Str::uuid();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'attachments' => [$file1, $file2],
        ])
        ->set('data.invoiceLines', [
            $uuid => [
                'product_id' => $product->id,
                'description' => 'Test Service',
                'quantity' => 1,
                'unit_price' => 100,
                'income_account_id' => $this->incomeAccount->id,
            ],
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
});

it('can upload attachments to existing draft invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Draft,
    ]);

    // Ensure line has income account (Factory creates it usually, but let's be sure if failures persist)
    // Actually factory lines use factory product which usually sets account.
    // But let's trust InvoiceFactory here.

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
});

it('prevents attachment upload on posted invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
        'company_id' => $this->company->id,
        'status' => InvoiceStatus::Posted,
    ]);

    Livewire::test(EditInvoice::class, ['record' => $invoice->id])
        ->assertFormFieldIsDisabled('attachments');
});

it('can delete attachments from draft invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
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
        'company_id' => $this->company->id,
        // attachments attribute not needed for direct create
    ]);

    expect(Storage::disk('local')->exists($filePath))->toBeTrue();

    // Delete attachment
    $attachment->delete();

    // Verify file was deleted from storage
    expect(Storage::disk('local')->exists($filePath))->toBeFalse();

    // Verify attachment was deleted from database
    expect($invoice->fresh()->attachments)->toHaveCount(0);
});

it('prevents attachment deletion from posted invoice', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
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
});

it('displays existing attachments in edit form', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
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
});

it('supports multiple file types in filament upload', function () {
    $customer = Partner::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::factory()->create();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'income_account_id' => $this->incomeAccount->id,
        'unit_price' => 100,
    ]);

    $fileTypes = [
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('image.jpg', 50, 'image/jpeg'),
        UploadedFile::fake()->create('sheet.xlsx', 75, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    ];

    // Generate UUID
    $uuid = (string) \Illuminate\Support\Str::uuid();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'attachments' => $fileTypes,
        ])
        ->set('data.invoiceLines', [
            $uuid => [
                'product_id' => $product->id,
                'description' => 'Test Service',
                'quantity' => 1,
                'unit_price' => 100,
                'income_account_id' => $this->incomeAccount->id,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::latest()->first();
    expect($invoice->attachments)->toHaveCount(3); // 3 files

    // Verify different MIME types
    $mimeTypes = $invoice->attachments->pluck('mime_type')->toArray();
    expect($mimeTypes)->toContain('application/pdf');
    expect($mimeTypes)->toContain('image/jpeg');
    expect($mimeTypes)->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('validates max file size', function () {
    $customer = Partner::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::factory()->create();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'income_account_id' => $this->incomeAccount->id,
        'unit_price' => 100,
    ]);

    // Create a file larger than 10MB (10240KB)
    $largeFile = UploadedFile::fake()->create('large-file.pdf', 11000, 'application/pdf');

    // Generate UUID
    $uuid = (string) \Illuminate\Support\Str::uuid();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'attachments' => [$largeFile],
        ])
        ->set('data.invoiceLines', [
            $uuid => [
                'product_id' => $product->id,
                'description' => 'Test Service',
                'quantity' => 1,
                'unit_price' => 100,
                'income_account_id' => $this->incomeAccount->id,
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['attachments']);
});

it('stores uploader information', function () {
    $invoice = Invoice::factory()->withLines(1)->create([
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
});
