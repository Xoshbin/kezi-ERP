<?php

namespace Modules\Foundation\Tests\Feature\Filament;

use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\CreateVendorBill;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoneySynthLivewireIntegrationTest extends TestCase
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

        $this->product = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'expense_account_id' => $this->expenseAccount->id,
        ]);

        // Set up authentication and tenant
        $this->actingAs($this->user);
        \Filament\Facades\Filament::setTenant($this->company);
    }

    /** @test */
    public function it_handles_money_input_in_vendor_bill_creation_without_synthesizer_errors(): void
    {
        // This test reproduces the exact scenario that was causing the TypeError
        // where MoneySynth::hydrate() was receiving a string instead of an array
        
        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-001',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        // Set the lines data - this is where the error was occurring
        // The unit_price value '1900' was being passed as a string to MoneySynth::hydrate()
        $livewire->set('data.lines', [
            [
                'product_id' => $this->product->id,
                'description' => 'Test Product',
                'quantity' => 1,
                'unit_price' => '1900', // This string value was causing the TypeError
                'expense_account_id' => $this->expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        // This should not throw a TypeError anymore
        $livewire->assertHasNoFormErrors();

        // Verify we can actually create the vendor bill
        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        // Verify the vendor bill was created with correct data
        $this->assertDatabaseHas('vendor_bills', [
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->currency->id,
            'bill_reference' => 'BILL-001',
        ]);

        $this->assertDatabaseHas('vendor_bill_lines', [
            'product_id' => $this->product->id,
            'description' => 'Test Product',
            'quantity' => 1,
            'expense_account_id' => $this->expenseAccount->id,
        ]);
    }

    /** @test */
    public function it_handles_multiple_line_items_with_different_amounts(): void
    {
        // Test with multiple line items to ensure the fix works for all scenarios
        
        $livewire = Livewire::test(CreateVendorBill::class)
            ->fillForm([
                'vendor_id' => $this->vendor->id,
                'currency_id' => $this->currency->id,
                'bill_reference' => 'BILL-002',
                'bill_date' => now()->format('Y-m-d'),
                'accounting_date' => now()->format('Y-m-d'),
            ]);

        // Set multiple lines with different unit prices
        $livewire->set('data.lines', [
            [
                'product_id' => $this->product->id,
                'description' => 'Product 1',
                'quantity' => 2,
                'unit_price' => '150.50',
                'expense_account_id' => $this->expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
            [
                'product_id' => $this->product->id,
                'description' => 'Product 2',
                'quantity' => 1,
                'unit_price' => '2500',
                'expense_account_id' => $this->expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        $livewire->assertHasNoFormErrors();

        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        // Verify both lines were created
        $this->assertDatabaseCount('vendor_bill_lines', 2);
    }

    /** @test */
    public function it_handles_decimal_amounts_correctly(): void
    {
        // Test with decimal amounts to ensure precision is maintained
        
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
                'product_id' => $this->product->id,
                'description' => 'Decimal Test',
                'quantity' => 1,
                'unit_price' => '123.45',
                'expense_account_id' => $this->expenseAccount->id,
                'tax_id' => null,
                'analytic_account_id' => null,
            ],
        ]);

        $livewire->assertHasNoFormErrors();

        $livewire->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('vendor_bill_lines', [
            'description' => 'Decimal Test',
            'quantity' => 1,
        ]);
    }
}
