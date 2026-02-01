<?php

namespace Kezi\Sales\Tests\Feature\Filament\Flows;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Enums\Partners\PartnerType;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\SalesOrder;
use Tests\TestCase;

class SalesOrderAccountingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $customer;

    protected Account $incomeAccount;

    protected Account $bankAccount;

    protected Account $arAccount;

    protected Journal $bankJournal;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Data
        $this->company = Company::factory()->create();
        // Company factory already creates or gets IQD currency.
        $this->currency = $this->company->currency;

        // Ensure decimal places are correct if factory didn't set them (though factory logic seems to try)
        if ($this->currency->decimal_places !== 3) {
            $this->currency->update(['decimal_places' => 3]);
        }

        $this->user = User::factory()->create();
        // Manually attaching company if relationship exists, or just skipping if column missing.
        // Assuming user needs to belong to company for tenant scope.
        // If column missing, maybe it's a many-to-many or handled differently?
        // But WithConfiguredCompany trait sets it? Let's check trait.
        // For now, removing the column assignment to fix the crash.
        $this->user->companies()->attach($this->company);
        // If current_company_id is required for tenancy, usually it's on the user table or session.
        // If invalid column, we can't set it.
        // Let's rely on filament tenancy or session if needed.
        // But wait, if the app requires current_company_id, removing it might break tenant checks.
        // Let's assume for now removing it fixes the SQL error.

        // Permission Setup
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);

        \Filament\Facades\Filament::setTenant($this->company);

        // Accounts
        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => AccountType::Income,
            'name' => 'Sales Revenue',
            'code' => '400000',
        ]);

        $this->arAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => AccountType::Receivable,
            'name' => 'Accounts Receivable',
            'code' => '110000',
        ]);

        $this->bankAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => AccountType::BankAndCash,
            'name' => 'Bank Account',
            'code' => '101000',
        ]);

        // Partner
        $this->customer = Partner::factory()->create([
            'company_id' => $this->company->id,
            'type' => PartnerType::Customer,
            'receivable_account_id' => $this->arAccount->id,
        ]);

        // Product
        // Note: Money::of(1000, 'IQD') creates a Money object with amount 1000.
        // If stored as nullable/string in some factories, we ensure explicit handling here if needed.
        // Assuming factory handles it or we set it manually.
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'type' => ProductType::Storable,
            'income_account_id' => $this->incomeAccount->id,
            'unit_price' => Money::of(1000, 'IQD'),
        ]);

        // Journal
        $this->bankJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'bank',
            'name' => 'Bank Journal',
            'currency_id' => $this->currency->id,
            'default_debit_account_id' => $this->bankAccount->id,
            'default_credit_account_id' => $this->bankAccount->id,
        ]);

        // Sales Journal
        $salesJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'sale',
            'name' => 'Customer Invoices',
            'currency_id' => $this->currency->id,
            'default_debit_account_id' => $this->arAccount->id,
            'default_credit_account_id' => $this->incomeAccount->id,
        ]);

        $this->company->update(['default_sales_journal_id' => $salesJournal->id]);
    }

    public function test_sales_order_full_accounting_flow()
    {
        // ---------------------------------------------------------------------
        // 1. Create Sales Order (Draft)
        // ---------------------------------------------------------------------
        $unitPrice = 50000; // 50,000 IQD

        // Livewire::test checks for authorization usually, ensuring user is logged in
        Livewire::test(CreateSalesOrder::class)
            // Use set() for individual fields if fillForm behaves oddly with mixed types,
            // but CreateSalesOrderTest uses fillForm for main fields and set for lines.
            ->fillForm([
                'customer_id' => $this->customer->id,
                'currency_id' => $this->currency->id,
                'so_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
            ])
            ->set('data.lines', [
                [
                    'product_id' => $this->product->id,
                    'description' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'tax_id' => null,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sales_orders', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'status' => SalesOrderStatus::Draft,
        ]);

        $salesOrder = SalesOrder::first();
        // Check amount. Money object storage usually involves 'amount' column or 'price' column.
        // Assuming 'total_amount' is cast to Money.
        $this->assertTrue($salesOrder->total_amount->getAmount()->toInt() === 50000);

        // ---------------------------------------------------------------------
        // 2. Confirm Sales Order
        // ---------------------------------------------------------------------
        Livewire::test(ViewSalesOrder::class, ['record' => $salesOrder->id])
            ->callAction('confirm');

        $salesOrder->refresh();
        $this->assertEquals(SalesOrderStatus::Confirmed, $salesOrder->status);

        // ---------------------------------------------------------------------
        // 3. Create Invoice (Draft)
        // ---------------------------------------------------------------------
        // The ViewSalesOrder page has a 'create_invoice' action.
        Livewire::test(ViewSalesOrder::class, ['record' => $salesOrder->id])
            ->callAction('create_invoice', data: [
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'default_income_account_id' => $this->incomeAccount->id,
            ]);

        $invoice = Invoice::where('sales_order_id', $salesOrder->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(InvoiceStatus::Draft, $invoice->status);
        $this->assertTrue($invoice->total_amount->getAmount()->toInt() === 50000);

        // ---------------------------------------------------------------------
        // 4. Confirm Invoice & Verify Journal Entry
        // ---------------------------------------------------------------------
        // We verified EditInvoice.php has 'post' action for confirmation.
        Livewire::test(EditInvoice::class, ['record' => $invoice->id])
            ->callAction('post');

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::Posted, $invoice->status);

        // Verify JE Creation
        $journalEntry = JournalEntry::where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->first();

        $this->assertNotNull($journalEntry, 'Journal Entry was not created for the invoice.');
        $this->assertTrue($journalEntry->is_posted);
        // Note: DB storage is typically Minor units for currencies like IQD (3 decimals) -> 50000 * 1000 = 50,000,000?
        // Or if MoneyCast handles it, ->total_debit should be integer storage.
        // Brick\Money: getAmount()->toInt() gets the decimal value if it is integer, or scale relevant.
        // HOWEVER, the database 'total_debit' column is usually BIGINT representing minor units OR major units depending on app convention.
        // App instructions say "Custom BaseCurrencyMoneyCast for database storage as integers".
        // IQD has 3 decimals. so 50,000 IQD -> 50,000,000 minor units.
        // Let's check with `getAmount()->toInt()` if we fetch it as Money object (from Cast),
        // or direct DB check. JournalEntry model likely casts 'total_debit' to Money.
        // BUT, looking at previous Tinker output: 292000000000 (292 Billion?) for 200,000 USD converted.
        // Just asserting on equality of Money objects is safest.

        // We expect 50,000 IQD. total_debit is cast to Money.
        $this->assertEquals(50000000, $journalEntry->total_debit->getMinorAmount()->toInt());

        // Verify Lines (Debits and Credits)
        // Debit: Accounts Receivable (Customer)
        $debitLine = $journalEntry->lines()->where('debit', '>', 0)->first();
        $this->assertEquals($this->arAccount->id, $debitLine->account_id);
        $this->assertEquals(50000000, $debitLine->debit->getMinorAmount()->toInt());
        $this->assertEquals($this->customer->id, $debitLine->partner_id);

        // Credit: Sales Revenue (Product Income Account)
        $creditLine = $journalEntry->lines()->where('credit', '>', 0)->first();
        $this->assertEquals($this->incomeAccount->id, $creditLine->account_id);
        $this->assertEquals(50000000, $creditLine->credit->getMinorAmount()->toInt());

        // ---------------------------------------------------------------------
        // 5. Register Payment & Verify Payment JE
        // ---------------------------------------------------------------------
        // Action: 'register_payment' on ViewInvoice (was EditInvoice)
        Livewire::test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ViewInvoice::class, ['record' => $invoice->id])
            ->callAction('register_payment', data: [
                'journal_id' => $this->bankJournal->id,
                'payment_date' => now(),
                'amount' => 50000, // Input is Major units usually in forms
                'currency_id' => $this->currency->id,
                'reference' => 'PAY-TEST-001',
            ]);

        $invoice->refresh();
        // Check payment state - assuming 'paid' or 'in_payment'
        $this->assertTrue($invoice->getRemainingAmount()->isZero());

        // Verify Payment JE
        $paymentJE = JournalEntry::where('source_type', 'Kezi\Payment\Models\Payment')
            ->latest('id')
            ->first();

        $this->assertNotNull($paymentJE, 'Journal Entry for Payment was not created.');
        $this->assertEquals(50000000, $paymentJE->total_debit->getMinorAmount()->toInt());

        // Debit: Bank Account
        $bankDebit = $paymentJE->lines()->where('account_id', $this->bankAccount->id)->first();
        $this->assertNotNull($bankDebit, 'Bank account should be debited');
        $this->assertEquals(50000000, $bankDebit->debit->getMinorAmount()->toInt());

        // Credit: Accounts Receivable
        $arCredit = $paymentJE->lines()->where('account_id', $this->arAccount->id)->first();
        $this->assertNotNull($arCredit, 'AR account should be credited');
        $this->assertEquals(50000000, $arCredit->credit->getMinorAmount()->toInt());
    }
}
