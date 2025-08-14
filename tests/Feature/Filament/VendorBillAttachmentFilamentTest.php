<?php

use App\Filament\Resources\VendorBillResource;
use App\Models\Account;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VendorBill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Traits\WithConfiguredCompany;

function livewire(string $component, array $parameters = [])
{
    return \Livewire\Livewire::test($component, $parameters);
}

uses(WithConfiguredCompany::class);

beforeEach(function () {
    // Set up storage for testing
    Storage::fake('local');
});

it('can create vendor bill with attachments', function () {
    /** @var \App\Models\Partner $vendor */
    $vendor = Partner::factory()->vendor()->for($this->company)->create();

    /** @var \App\Models\Account $expenseAccount */
    $expenseAccount = Account::factory()->for($this->company)->create();

    /** @var \App\Models\Product $product */
    $product = Product::factory()->for($this->company)->create([
        'expense_account_id' => $expenseAccount->id,
        'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code),
    ]);

    $file = UploadedFile::fake()->create('test-invoice.pdf', 100, 'application/pdf');

    livewire(VendorBillResource\Pages\CreateVendorBill::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'bill_reference' => 'BILL-001',
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'attachments' => [$file],
        ])
        ->set('data.lines', [
            [
                'product_id' => $product->id,
                'description' => 'Test Product',
                'quantity' => 1,
                'unit_price' => 100,
                'expense_account_id' => $expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Check that vendor bill was created
    expect(VendorBill::where('bill_reference', 'BILL-001')->exists())->toBeTrue();

    // Check that attachment was created
    $vendorBill = VendorBill::where('bill_reference', 'BILL-001')->first();
    expect($vendorBill->attachments)->toHaveCount(1);

    $attachment = $vendorBill->attachments->first();
    expect($attachment->file_name)->toEndWith('.pdf');
    expect($attachment->mime_type)->toBe('application/pdf');
    expect($attachment->uploaded_by_user_id)->toBe($this->user->id);
});
