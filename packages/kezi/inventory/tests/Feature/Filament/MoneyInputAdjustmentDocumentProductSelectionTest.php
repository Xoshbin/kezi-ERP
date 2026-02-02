<?php

namespace Kezi\Inventory\Tests\Feature\Filament;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\CreateAdjustmentDocument;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Product\Models\Product;
use Livewire\Livewire;
use Tests\TestCase;

class MoneyInputAdjustmentDocumentProductSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private Currency $currency;

    private Product $product;

    private Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()->createSafely(['code' => 'USD']);

        $this->company->update(['currency_id' => $this->currency->id]);

        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'income',
        ]);

        // Create a product with a specific Money unit price
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Adjustment Product',
            'description' => 'Test product for adjustment document',
            'unit_price' => Money::of('75.50', $this->currency->code), // Specific price to test
            'income_account_id' => $this->incomeAccount->id,
        ]);

        // Set up authentication and tenant
        $this->actingAs($this->user);
        Filament::setTenant($this->company);
    }

    /** @test */
    public function it_populates_unit_price_as_string_when_product_is_selected_in_adjustment_document(): void
    {
        $livewire = Livewire::test(CreateAdjustmentDocument::class)
            ->fillForm([
                'type' => 'credit_note',
                'reference' => 'ADJ-001',
                'date' => now()->format('Y-m-d'),
                'currency_id' => $this->currency->id,
            ]);

        // Initially set up a line with empty data
        $livewire->set('data.lines', [
            [
                'product_id' => null,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'account_id' => null,
                'tax_id' => null,
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
        $this->assertEquals('75.50', $lineData['unit_price']);
        $this->assertNotEquals('[object Object]', $lineData['unit_price']);

        // Also verify other fields were populated correctly
        $this->assertEquals($this->product->name, $lineData['description']);
        $this->assertEquals($this->product->income_account_id, $lineData['account_id']);
    }

    /** @test */
    public function it_handles_products_with_different_price_formats_in_adjustment_document(): void
    {
        // Test with integer price
        $integerProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Integer Price Product',
            'description' => 'Product with integer price',
            'unit_price' => Money::of('200', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        // Test with decimal price
        $decimalProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Decimal Price Product',
            'description' => 'Product with decimal price',
            'unit_price' => Money::of('33.33', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateAdjustmentDocument::class)
            ->fillForm([
                'type' => 'credit_note',
                'reference' => 'ADJ-002',
                'date' => now()->format('Y-m-d'),
                'currency_id' => $this->currency->id,
            ]);

        // Test integer price
        $livewire->set('data.lines', [
            [
                'product_id' => $integerProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');
        $this->assertEquals('200', $formData['lines'][0]['unit_price']);

        // Test decimal price
        $livewire->set('data.lines.0.product_id', $decimalProduct->id);

        $formData = $livewire->get('data');
        $this->assertEquals('33.33', $formData['lines'][0]['unit_price']);
    }

    /** @test */
    public function it_handles_products_with_null_unit_price_in_adjustment_document(): void
    {
        // Create a product with null unit price
        $nullPriceProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'No Price Product',
            'description' => 'Product without price',
            'unit_price' => null,
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateAdjustmentDocument::class)
            ->fillForm([
                'type' => 'credit_note',
                'reference' => 'ADJ-003',
                'date' => now()->format('Y-m-d'),
                'currency_id' => $this->currency->id,
            ]);

        $livewire->set('data.lines', [
            [
                'product_id' => $nullPriceProduct->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');

        // Should handle null gracefully
        $this->assertNull($formData['lines'][0]['unit_price']);
        $this->assertNotEquals('[object Object]', $formData['lines'][0]['unit_price']);
    }

    /** @test */
    public function it_populates_description_from_product_name_in_adjustment_document(): void
    {
        // Verify that adjustment documents use product name for description (like vendor bills)
        $productWithName = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Specific Product Name',
            'description' => 'Different description text',
            'unit_price' => Money::of('50.00', $this->currency->code),
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $livewire = Livewire::test(CreateAdjustmentDocument::class)
            ->fillForm([
                'type' => 'credit_note',
                'reference' => 'ADJ-004',
                'date' => now()->format('Y-m-d'),
                'currency_id' => $this->currency->id,
            ]);

        $livewire->set('data.lines', [
            [
                'product_id' => $productWithName->id,
                'description' => '',
                'quantity' => 1,
                'unit_price' => '',
                'account_id' => null,
                'tax_id' => null,
            ],
        ]);

        $formData = $livewire->get('data');

        // Should populate description field with product name, not description
        $this->assertEquals('Specific Product Name', $formData['lines'][0]['description']);
        $this->assertNotEquals('Different description text', $formData['lines'][0]['description']);
    }

    /** @test */
    public function it_can_create_adjustment_document_after_product_selection(): void
    {
        // End-to-end test to ensure the fix doesn't break the creation process

        $livewire = Livewire::test(CreateAdjustmentDocument::class)
            ->fillForm([
                'type' => 'credit_note',
                'reference' => 'ADJ-005',
                'date' => now()->format('Y-m-d'),
                'currency_id' => $this->currency->id,
            ]);

        // Select product and verify the price is populated correctly
        $livewire->set('data.lines', [
            [
                'product_id' => $this->product->id,
                'description' => 'Test Adjustment Product',
                'quantity' => 2,
                'unit_price' => '75.50', // This should be auto-populated from product selection
                'account_id' => $this->incomeAccount->id,
                'tax_id' => null,
            ],
        ]);

        // Should be able to create the adjustment document without errors
        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        // Verify the adjustment document was created in the database
        $this->assertDatabaseHas('adjustment_documents', [
            'type' => 'credit_note',
            'reference' => 'ADJ-005',
            'currency_id' => $this->currency->id,
        ]);

        $this->assertDatabaseHas('adjustment_document_lines', [
            'product_id' => $this->product->id,
            'description' => 'Test Adjustment Product',
            'quantity' => 2,
        ]);
    }
}
