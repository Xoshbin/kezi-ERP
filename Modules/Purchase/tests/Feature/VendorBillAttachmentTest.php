<?php

namespace Modules\Purchase\Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorBillAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected \Modules\Purchase\Models\VendorBill $vendorBill;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        $currency = \Modules\Foundation\Models\Currency::factory()->create();
        $vendor = \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id]);

        $this->vendorBill = \Modules\Purchase\Models\VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $vendor->id,
            'currency_id' => $currency->id,
        ]);

        // Set up storage for testing
        Storage::fake('local');
    }

    /** @test */
    public function it_can_create_vendor_bill_attachment()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 100, 'application/pdf');
        $filePath = $file->store('vendor-bill-attachments', 'local');

        $attachment = VendorBillAttachment::create([
            'company_id' => $this->vendorBill->company_id,
            'vendor_bill_id' => $this->vendorBill->id,
            'file_name' => 'test-document.pdf',
            'file_path' => $filePath,
            'file_size' => 100 * 1024, // 100KB in bytes
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('vendor_bill_attachments', [
            'vendor_bill_id' => $this->vendorBill->id,
            'file_name' => 'test-document.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $this->user->id,
        ]);

        $this->assertEquals('test-document.pdf', $attachment->file_name);
        $this->assertEquals('application/pdf', $attachment->mime_type);
    }

    /** @test */
    public function it_can_access_vendor_bill_attachments_relationship()
    {
        $attachment = VendorBillAttachment::factory()->create([
            'vendor_bill_id' => $this->vendorBill->id,
            'uploaded_by_user_id' => $this->user->id,
        ]);

        $this->vendorBill->refresh();

        $this->assertCount(1, $this->vendorBill->attachments);
        $this->assertEquals($attachment->id, $this->vendorBill->attachments->first()->id);
    }

    /** @test */
    public function it_formats_file_size_correctly()
    {
        $attachment = VendorBillAttachment::factory()->create([
            'vendor_bill_id' => $this->vendorBill->id,
            'uploaded_by_user_id' => $this->user->id,
            'file_size' => 1024 * 1024, // 1MB
        ]);

        $this->assertEquals('1 MB', $attachment->formatted_file_size);
    }

    /** @test */
    public function it_can_check_if_file_exists()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 100, 'application/pdf');
        $filePath = $file->store('vendor-bill-attachments', 'local');

        $attachment = VendorBillAttachment::factory()->create([
            'vendor_bill_id' => $this->vendorBill->id,
            'uploaded_by_user_id' => $this->user->id,
            'file_path' => $filePath,
        ]);

        $this->assertTrue($attachment->fileExists());
    }

    /** @test */
    public function it_deletes_file_when_attachment_is_deleted()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 100, 'application/pdf');
        $filePath = $file->store('vendor-bill-attachments', 'local');

        $attachment = VendorBillAttachment::factory()->create([
            'vendor_bill_id' => $this->vendorBill->id,
            'uploaded_by_user_id' => $this->user->id,
            'file_path' => $filePath,
        ]);

        // Verify file exists
        $this->assertTrue(Storage::disk('local')->exists($filePath));

        // Delete attachment
        $attachment->delete();

        // Verify file is deleted
        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }
}
