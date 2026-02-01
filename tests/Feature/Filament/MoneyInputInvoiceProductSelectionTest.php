<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Accounting\Resources\Invoices\Pages\CreateInvoice;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoneyInputInvoiceProductSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private \Kezi\Foundation\Models\Currency $currency;

    private \Kezi\Foundation\Models\Partner $customer;

    private \Kezi\Product\Models\Product $product;

    private \Kezi\Accounting\Models\Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->currency = \Kezi\Foundation\Models\Currency::factory()->create(['code' => 'USD']);

        $this->company->update(['currency_id' => $this->currency->id]);

        $this->customer = \Kezi\Foundation\Models\Partner::factory()->customer()->create([
            'company_id' => $this->company->id,
        ]);

        $this->incomeAccount = \Kezi\Accounting\Models\Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'income',
        ]);

        // Create a product with a specific Money unit price
        $this->product = \Kezi\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Invoice Product',
            'description' => 'Test product for invoice',
            'unit_price' => Money::of('299.99', $this->currency->code), // Specific price to test
            'income_account_id' => $this->incomeAccount->id,
        ]);

        // Set up authentication and tenant
        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }

    /** @test */
    public function it_populates_unit_price_as_string_when_product_is_selected_in_invoice(): void
    {
        $livewire = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        // Initially set up a line with empty data
        $livewire->set('data.invoiceLines', [
            [
                'product_id' => null,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'income_account_id' => null,
                'tax_id' => null,
            ],
        ]);

        // Now select the product - this should trigger the afterStateUpdated callback
        $livewire->set('data.invoiceLines.0.product_id', $this->product->id);

        // Verify that the unit_price is populated as a string, not "[object Object]"
        $formData = $livewire->get('data');

        $this->assertIsArray($formData['invoiceLines']);
        $this->assertCount(1, $formData['invoiceLines']);

        $lineData = $formData['invoiceLines'][0];

        // The key assertion: unit_price should be a string representation of the amount
        $this->assertIsString($lineData['unit_price']);
        $this->assertEquals('299.99', $lineData['unit_price']);
        $this->assertNotEquals('[object Object]', $lineData['unit_price']);

        // Also verify other fields were populated correctly
        $this->assertEquals($this->product->description, $lineData['description']);
        $this->assertEquals($this->product->income_account_id, $lineData['income_account_id']);
    }

    /** @test */
    public function it_handles_products_with_different_price_formats_in_invoice(): void
    {
        // Test with integer price
        $integerProduct = \Kezi\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Integer Price Product',
            'description' => 'Product with integer price',
            'unit_price' => Money::of('500', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        // Test with decimal price
        $decimalProduct = \Kezi\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Decimal Price Product',
            'description' => 'Product with decimal price',
            'unit_price' => Money::of('49.95', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        // Test integer price
        $livewire->set('data.invoiceLines', [
            [
                'product_id' => $integerProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'income_account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');
        $this->assertEquals('500', $formData['invoiceLines'][0]['unit_price']);

        // Test decimal price
        $livewire->set('data.invoiceLines.0.product_id', $decimalProduct->id);

        $formData = $livewire->get('data');
        $this->assertEquals('49.95', $formData['invoiceLines'][0]['unit_price']);
    }

    /** @test */
    public function it_handles_products_with_null_unit_price_in_invoice(): void
    {
        // Create a product with null unit price
        $nullPriceProduct = \Kezi\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'No Price Product',
            'description' => 'Product without price',
            'unit_price' => null,
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        $livewire->set('data.invoiceLines', [
            [
                'product_id' => $nullPriceProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'income_account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');

        // Should handle null gracefully
        $this->assertNull($formData['invoiceLines'][0]['unit_price']);
        $this->assertNotEquals('[object Object]', $formData['invoiceLines'][0]['unit_price']);
    }

    /** @test */
    public function it_can_create_invoice_after_product_selection(): void
    {
        // End-to-end test to ensure the fix doesn't break the creation process

        $livewire = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        // Select product and verify the price is populated correctly
        $livewire->set('data.invoiceLines', [
            [
                'product_id' => $this->product->id,
                'description' => 'Test Invoice Product',
                'quantity' => 3,
                'unit_price' => '299.99', // This should be auto-populated from product selection
                'income_account_id' => $this->incomeAccount->id,
                'tax_id' => null,
            ],
        ]);

        // Should be able to create the invoice without errors
        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        // Verify the invoice was created in the database
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'product_id' => $this->product->id,
            'description' => 'Test Invoice Product',
            'quantity' => 3,
        ]);
    }

    /** @test */
    public function it_populates_description_from_product_description_not_name(): void
    {
        // Verify that the invoice uses product description, not name (unlike vendor bills)
        $productWithDescription = \Kezi\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Product Name',
            'description' => 'Detailed product description',
            'unit_price' => Money::of('100.00', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateInvoice::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        $livewire->set('data.invoiceLines', [
            [
                'product_id' => $productWithDescription->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'income_account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');

        // Should populate description field with product description, not name
        $this->assertEquals('Detailed product description', $formData['invoiceLines'][0]['description']);
        $this->assertNotEquals('Product Name', $formData['invoiceLines'][0]['description']);
    }
}
