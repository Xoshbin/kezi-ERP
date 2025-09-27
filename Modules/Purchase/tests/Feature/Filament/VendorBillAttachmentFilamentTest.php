<?php

namespace Modules\Purchase\Tests\Feature\Filament;

use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBillAttachmentFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected \Modules\Foundation\Models\Currency $currency;

    protected \Modules\Foundation\Models\Partner $vendor;

    protected \Modules\Accounting\Models\Account $expenseAccount;

    protected \Modules\Product\Models\Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        // Set up Filament tenant context
        \Filament\Facades\Filament::setTenant($this->company);

        $this->currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);
        $this->vendor = \Modules\Foundation\Models\Partner::factory()->create(['company_id' => $this->company->id]);
        $this->expenseAccount = \Modules\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);
        $this->product = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'expense_account_id' => $this->expenseAccount->id,
            'unit_price' => \Brick\Money\Money::of(100, $this->currency->code),
        ]);

        // Set up storage for testing
        Storage::fake('local');

        // Authenticate user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_vendor_bill_with_attachments()
    {
        $file = UploadedFile::fake()->create('test-invoice.pdf', 100, 'application/pdf');

        Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-001',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
                'attachments' => [$file],
            ])
            ->set('data.lines', [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'expense_account_id' => $this->expenseAccount->id,
                    'tax_id' => null,
                    'analytic_account_id' => null,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Check that vendor bill was created
        $this->assertDatabaseHas('vendor_bills', [
            'bill_reference' => 'BILL-001',
            'vendor_id' => $this->vendor->id,
        ]);

        // Check that attachment was created
        $vendorBill = \Modules\Purchase\Models\VendorBill::where('bill_reference', 'BILL-001')->first();
        $this->assertCount(1, $vendorBill->attachments);

        $attachment = $vendorBill->attachments->first();
        $this->assertStringEndsWith('.pdf', $attachment->file_name);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertEquals($this->user->id, $attachment->uploaded_by_user_id);
    }
}
