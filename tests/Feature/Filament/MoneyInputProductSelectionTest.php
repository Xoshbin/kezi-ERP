<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoneyInputProductSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private \Modules\Foundation\Models\Currency $currency;
    private \Modules\Foundation\Models\Partner $vendor;
    private \Modules\Product\Models\Product $product;
    private \Modules\Accounting\Models\Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);

        $this->company->update(['currency_id' => $this->currency->id]);

        $this->vendor = \Modules\Foundation\Models\Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $this->expenseAccount = \Modules\Accounting\Models\Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'expense',
        ]);

        // Create a product with a specific Money unit price
        $this->product = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'unit_price' => Money::of('150.75', $this->currency->code), // Specific price to test
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        // Set up authentication and tenant
        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }

    /** @test */
    public function it_populates_unit_price_as_string_when_product_is_selected(): void
    {
        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-001',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        // Initially set up a line with empty data
        $livewire->set('data.lines', [
            [
                'product_id' => null,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'expense_account_id' => null,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        // Now select the product - this should trigger the afterStateUpdated callback
        $livewire->set('data.lines.0.product_id', $this->product->id);

        // Verify that the unit_price is populated as a string, not "[object Object]"
        $formData = $livewire->get('data');

        $this->assertIsArray($formData['lines']);
        $this->assertCount(1, $formData['lines']);

        $lineData = $formData['lines'][0];

        // The key assertion: unit_price should be a string representation of the amount
        $this->assertIsString($lineData['unit_price']);
        $this->assertEquals('150.75', $lineData['unit_price']);
        $this->assertNotEquals('[object Object]', $lineData['unit_price']);

        // Also verify other fields were populated correctly
        $this->assertEquals($this->product->name, $lineData['description']);
        $this->assertEquals($this->product->expense_account_id, $lineData['expense_account_id']);
    }

    /** @test */
    public function it_handles_products_with_different_price_formats(): void
    {
        // Test with integer price
        $integerProduct = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Integer Price Product',
            'unit_price' => Money::of('100', $this->currency->code),
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        // Test with decimal price
        $decimalProduct = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Decimal Price Product',
            'unit_price' => Money::of('99.99', $this->currency->code),
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-002',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        // Test integer price
        $livewire->set('data.lines', [
            [
                'product_id' => $integerProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'expense_account_id' => null,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');
        $this->assertEquals('100', $formData['lines'][0]['unit_price']);

        // Test decimal price
        $livewire->set('data.lines.0.product_id', $decimalProduct->id);

        $formData = $livewire->get('data');
        $this->assertEquals('99.99', $formData['lines'][0]['unit_price']);
    }

    /** @test */
    public function it_handles_products_with_null_unit_price(): void
    {
        // Create a product with null unit price
        $nullPriceProduct = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'No Price Product',
            'unit_price' => null,
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-003',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        $livewire->set('data.lines', [
            [
                'product_id' => $nullPriceProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'expense_account_id' => null,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');

        // Should handle null gracefully
        $this->assertNull($formData['lines'][0]['unit_price']);
        $this->assertNotEquals('[object Object]', $formData['lines'][0]['unit_price']);
    }

    /** @test */
    public function it_can_create_vendor_bill_after_product_selection(): void
    {
        // End-to-end test to ensure the fix doesn't break the creation process

        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-004',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        // Select product and verify the price is populated correctly
        $livewire->set('data.lines', [
            [
                'product_id' => $this->product->id,
                'description' => 'Test Product',
                'quantity' => 2,
                'unit_price' => '150.75', // This should be auto-populated from product selection
                'expense_account_id' => $this->expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        // Should be able to create the vendor bill without errors
        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        // Verify the vendor bill was created in the database
        $this->assertDatabaseHas('vendor_bills', [
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'bill_reference' => 'BILL-004',
        ]);

        $this->assertDatabaseHas('vendor_bill_lines', [
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 2,
        ]);
    }
}
