<?php

namespace Jmeryar\Purchase\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Models\VendorBill;
use Tests\TestCase;

class VendorBillAttachmentFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $currency;

    protected Partner $vendor;

    protected Account $expenseAccount;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->actingAs($this->user);

        // Set up Filament tenant context
        Filament::setTenant($this->company);

        $this->currency = Currency::factory()->create(['code' => 'USD', 'decimal_places' => 2]);
        $this->vendor = Partner::factory()->create(['company_id' => $this->company->id]);
        $this->expenseAccount = Account::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'expense_account_id' => $this->expenseAccount->id,
            'unit_price' => Money::of(100, $this->currency->code),
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
        $vendorBill = VendorBill::where('bill_reference', 'BILL-001')->first();
        $this->assertCount(1, $vendorBill->attachments);

        $attachment = $vendorBill->attachments->first();
        $this->assertStringEndsWith('.pdf', $attachment->file_name);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertEquals($this->user->id, $attachment->uploaded_by_user_id);
    }
}
