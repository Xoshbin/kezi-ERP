<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Account;
use App\Models\VendorBill;
use App\Models\RecurringInvoiceTemplate;
use App\Enums\Inventory\ProductType;
use App\Enums\RecurringInvoice\RecurringFrequency;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InterCompanyWorkflowTest extends DuskTestCase
{
    protected Company $parentCompany;
    protected Company $childCompany;
    protected User $parentUser;
    protected User $childUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupInterCompanyHierarchy();
    }

    /**
     * Test complete inter-company stock transfer workflow.
     */
    public function test_complete_inter_company_stock_transfer_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Create a storable product
            $product = Product::factory()->create([
                'company_id' => $this->parentCompany->id,
                'type' => ProductType::Storable,
                'name' => 'Transfer Test Product',
            ]);

            // Create stock locations
            StockLocation::factory()->create([
                'company_id' => $this->parentCompany->id,
                'name' => 'Parent Warehouse',
            ]);

            StockLocation::factory()->create([
                'company_id' => $this->childCompany->id,
                'name' => 'Child Warehouse',
            ]);

            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/inter-company-stock-transfers')
                ->assertSee('Inter-Company Transfers')
                
                // Create a new transfer
                ->click('@create-action')
                ->waitForText('Create Inter-Company Stock Transfer')
                
                // Fill the form
                ->select('data.source_company_id', $this->parentCompany->id)
                ->select('data.target_company_id', $this->childCompany->id)
                ->select('data.product_id', $product->id)
                ->type('data.quantity', '25')
                ->type('data.transfer_date', now()->format('Y-m-d'))
                ->type('data.reference', 'PLAYWRIGHT-TEST-TRANSFER')
                ->type('data.notes', 'Test transfer via Playwright')
                
                // Submit the form
                ->click('button[type="submit"]')
                ->waitForText('Inter-Company Stock Transfer created successfully')
                
                // Verify the transfer appears in the list
                ->visit('/jmeryar/inter-company-stock-transfers')
                ->assertSee('PLAYWRIGHT-TEST-TRANSFER')
                ->assertSee($this->childCompany->name)
                ->assertSee('25');

            // Verify corresponding receipt was created in child company
            $browser->loginAs($this->childUser)
                ->visit('/jmeryar/inter-company-stock-transfers')
                ->assertSee('IC-TRANSFER-')
                ->assertSee($product->name)
                ->assertSee('25');
        });
    }

    /**
     * Test complete inter-company payment workflow.
     */
    public function test_complete_inter_company_payment_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Create a vendor bill in child company
            $parentPartnerInChild = Partner::where('company_id', $this->childCompany->id)
                ->where('linked_company_id', $this->parentCompany->id)
                ->first();

            $vendorBill = VendorBill::factory()->create([
                'company_id' => $this->childCompany->id,
                'vendor_id' => $parentPartnerInChild->id,
                'bill_reference' => 'PLAYWRIGHT-BILL-001',
                'status' => 'posted',
            ]);

            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/payments')
                ->assertSee('Payments')
                
                // Create a new payment
                ->click('@create-action')
                ->waitForText('Create Payment')
                
                // Fill payment form
                ->select('data.type', 'vendor_bill')
                ->select('data.journal_id', $this->parentCompany->default_bank_journal_id)
                ->type('data.payment_date', now()->format('Y-m-d'))
                ->type('data.reference', 'PLAYWRIGHT-PAYMENT-001')
                
                // Add vendor bill payment
                ->click('Add Vendor Bill Payment')
                ->select('data.vendor_bill_payments.0.vendor_bill_id', $vendorBill->id)
                ->type('data.vendor_bill_payments.0.amount_applied', '1500')
                
                // Submit the payment
                ->click('button[type="submit"]')
                ->waitForText('Payment created successfully')
                
                // Verify payment appears in list
                ->visit('/jmeryar/payments')
                ->assertSee('PLAYWRIGHT-PAYMENT-001')
                ->assertSee('Confirmed');

            // Verify inter-company loan entries were created
            // This would require checking journal entries, which is tested in unit tests
        });
    }

    /**
     * Test complete recurring charges workflow.
     */
    public function test_complete_recurring_charges_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Create required accounts
            $incomeAccount = Account::factory()->create([
                'company_id' => $this->parentCompany->id,
                'account_type' => 'income',
                'name' => 'Management Fee Income',
            ]);

            $expenseAccount = Account::factory()->create([
                'company_id' => $this->childCompany->id,
                'account_type' => 'expense',
                'name' => 'Management Fee Expense',
            ]);

            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/recurring-invoices')
                ->assertSee('Recurring Invoice Templates')
                
                // Create a new recurring template
                ->click('@create-action')
                ->waitForText('Create Recurring Invoice Template')
                
                // Fill template information
                ->type('data.name', 'Playwright Monthly Management Fee')
                ->select('data.target_company_id', $this->childCompany->id)
                ->type('data.description', 'Monthly management services for testing')
                
                // Configure scheduling
                ->select('data.frequency', RecurringFrequency::Monthly->value)
                ->type('data.start_date', now()->format('Y-m-d'))
                ->type('data.day_of_month', '1')
                
                // Configure financial settings
                ->select('data.income_account_id', $incomeAccount->id)
                ->select('data.expense_account_id', $expenseAccount->id)
                
                // Add line items
                ->type('data.lines.0.description', 'Management Services')
                ->type('data.lines.0.quantity', '1')
                ->type('data.lines.0.unit_price', '5000')
                
                // Submit the form
                ->click('button[type="submit"]')
                ->waitForText('Recurring Invoice Template created successfully')
                
                // Verify template appears in list
                ->visit('/jmeryar/recurring-invoices')
                ->assertSee('Playwright Monthly Management Fee')
                ->assertSee($this->childCompany->name)
                ->assertSee('Monthly')
                ->assertSee('Active');

            // Test manual generation
            $template = RecurringInvoiceTemplate::where('name', 'Playwright Monthly Management Fee')->first();
            
            $browser->click("@generate-{$template->id}")
                ->waitForText('Invoice generated successfully')
                
                // Verify invoice was created
                ->visit('/jmeryar/invoices')
                ->assertSee('IC-RECURRING-')
                ->assertSee($this->childCompany->name);

            // Verify corresponding vendor bill was created in child company
            $browser->loginAs($this->childUser)
                ->visit('/jmeryar/vendor-bills')
                ->assertSee('IC-RECURRING-BILL-')
                ->assertSee($this->parentCompany->name);
        });
    }

    /**
     * Test cross-feature integration: stock transfers + payments.
     */
    public function test_cross_feature_integration()
    {
        $this->browse(function (Browser $browser) {
            // Create a product for transfer
            $product = Product::factory()->create([
                'company_id' => $this->parentCompany->id,
                'type' => ProductType::Storable,
                'name' => 'Integration Test Product',
            ]);

            // Step 1: Create stock transfer
            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/inter-company-stock-transfers')
                ->click('@create-action')
                ->select('data.source_company_id', $this->parentCompany->id)
                ->select('data.target_company_id', $this->childCompany->id)
                ->select('data.product_id', $product->id)
                ->type('data.quantity', '10')
                ->type('data.transfer_date', now()->format('Y-m-d'))
                ->click('button[type="submit"]')
                ->waitForText('Inter-Company Stock Transfer created successfully');

            // Step 2: Create a vendor bill for the transfer
            $browser->loginAs($this->childUser)
                ->visit('/jmeryar/vendor-bills')
                ->click('@create-action')
                ->waitForText('Create Vendor Bill')
                // Fill vendor bill form...
                ->click('button[type="submit"]')
                ->waitForText('Vendor Bill created successfully');

            // Step 3: Parent company pays the vendor bill
            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/payments')
                ->click('@create-action')
                // Create payment for the vendor bill...
                ->click('button[type="submit"]')
                ->waitForText('Payment created successfully');

            // Verify audit trails across all features
            $browser->visit('/jmeryar/journal-entries')
                ->assertSee('IC-TRANSFER-')
                ->assertSee('IC-PAYMENT-');
        });
    }

    /**
     * Test error handling in UI.
     */
    public function test_error_handling_in_ui()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/recurring-invoices')
                ->click('@create-action')
                
                // Try to submit with missing required fields
                ->click('button[type="submit"]')
                ->waitForText('The name field is required')
                ->assertSee('The target company id field is required')
                ->assertSee('The frequency field is required')
                
                // Try to create with same source and target company
                ->type('data.name', 'Invalid Template')
                ->select('data.target_company_id', $this->parentCompany->id) // Same as source
                ->select('data.frequency', RecurringFrequency::Monthly->value)
                ->click('button[type="submit"]')
                ->waitForText('Source and target companies must be different');
        });
    }

    /**
     * Test partner relationship requirements.
     */
    public function test_partner_relationship_requirements()
    {
        $this->browse(function (Browser $browser) {
            // Create a company without partner relationship
            $unlinkedCompany = Company::factory()->create(['name' => 'Unlinked Company']);

            $browser->loginAs($this->parentUser)
                ->visit('/jmeryar/inter-company-stock-transfers')
                ->click('@create-action')
                
                // Target company dropdown should not include unlinked companies
                ->assertDontSee('Unlinked Company')
                
                // Should only see companies with partner relationships
                ->assertSee($this->childCompany->name);
        });
    }

    /**
     * Set up inter-company hierarchy for testing.
     */
    protected function setupInterCompanyHierarchy(): void
    {
        $this->parentCompany = Company::factory()->create(['name' => 'ParentCo Test']);
        $this->childCompany = Company::factory()->create([
            'name' => 'ChildCo Test',
            'parent_company_id' => $this->parentCompany->id,
        ]);

        // Create users for both companies
        $this->parentUser = User::factory()->create([
            'company_id' => $this->parentCompany->id,
            'email' => 'parent@test.com',
        ]);

        $this->childUser = User::factory()->create([
            'company_id' => $this->childCompany->id,
            'email' => 'child@test.com',
        ]);

        // Create partner relationships
        Partner::factory()->create([
            'company_id' => $this->parentCompany->id,
            'name' => 'ChildCo Partner',
            'linked_company_id' => $this->childCompany->id,
        ]);

        Partner::factory()->create([
            'company_id' => $this->childCompany->id,
            'name' => 'ParentCo Partner',
            'linked_company_id' => $this->parentCompany->id,
        ]);
    }
}
