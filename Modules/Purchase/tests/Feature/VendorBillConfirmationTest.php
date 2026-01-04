<?php

namespace Modules\Purchase\tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill as FilamentEditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal; // Adjust namespace if needed
use Modules\Foundation\Models\Currency;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Purchase\Services\VendorBillService;
use Tests\TestCase;

class VendorBillConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Account $payableAccount;

    protected Account $expenseAccount;

    protected Account $inputTaxAccount;

    protected Journal $journal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company);

        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('super_admin');

        $this->actingAs($this->user);
        \Filament\Facades\Filament::setTenant($this->company);

        $this->company->update(['currency_id' => Currency::factory()->create(['code' => 'USD'])->id]);

        $this->payableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2000',
            'name' => 'Accounts Payable',
            'type' => AccountType::Payable,
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '6000',
            'name' => 'Expenses',
            'name' => 'Expenses',
            'type' => AccountType::Expense,
        ]);

        $this->inputTaxAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '2500',
            'name' => 'Input Tax',
            'type' => AccountType::CurrentAssets, // Or appropriate type
        ]);

        $this->journal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'purchase',
            'name' => 'Vendor Bills',
        ]);

        $this->company->update([
            'default_accounts_payable_id' => $this->payableAccount->id,
            'default_purchase_journal_id' => $this->journal->id,
            'default_tax_receivable_id' => $this->inputTaxAccount->id,
        ]); // Configure company defaults
        $this->company->update([
            'default_accounts_payable_id' => $this->payableAccount->id,
            'default_purchase_journal_id' => $this->journal->id,
        ]);
    }

    public function test_vendor_bill_posting_preview_action_runs_without_errors_on_valid_bill()
    {
        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'expense_account_id' => $this->expenseAccount->id,
            'quantity' => 1,
            'unit_price' => 10000, // $100.00
            'subtotal' => 10000,
        ]);

        $bill->refresh();
        $bill->calculateTotalsFromLines();

        $action = app(BuildVendorBillPostingPreviewAction::class);
        $result = $action->execute($bill);

        $this->assertIsArray($result);
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['lines']);
        $this->assertTrue($result['totals']['balanced']);
    }

    public function test_vendor_bill_posting_preview_returns_errors_on_missing_config()
    {
        // Unconfigure company defaults
        $this->company->update([
            'default_accounts_payable_id' => null,
            'default_purchase_journal_id' => null,
        ]);

        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'expense_account_id' => $this->expenseAccount->id,
            'quantity' => 1,
            'unit_price' => 10000, // $100.00
            'subtotal' => 10000,
        ]);

        $bill->refresh();
        $bill->calculateTotalsFromLines();

        $action = app(BuildVendorBillPostingPreviewAction::class);
        $result = $action->execute($bill);

        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Company default Accounts Payable account is not configured.', $result['errors']);
        $this->assertContains('Company default purchase journal is not configured.', $result['errors']);

        // Assert view renders without error even with missing config
        $view = view('accounting::filament.clusters.accounting.resources.vendor-bills.pages.preview-posting', ['preview' => $result, 'bill' => $bill]);
        $rendered = $view->render();
        $this->assertStringContainsString('Company default Accounts Payable account is not configured', $rendered);
    }

    public function test_confirm_action_works_formally()
    {
        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'expense_account_id' => $this->expenseAccount->id,
            'quantity' => 1,
            'unit_price' => 10000, // $100.00
            'subtotal' => 10000,
        ]);

        $bill->refresh();
        $bill->calculateTotalsFromLines();

        $service = app(VendorBillService::class);
        $service->confirm($bill, $this->user);

        $bill->refresh();
        $this->assertEquals(VendorBillStatus::Posted, $bill->status);
        $this->assertNotNull($bill->journalEntry);
    }

    // Test the Filament Page action specifically to see if it catches exceptions
    public function test_filament_action_handles_confirmation()
    {
        // Config is valid from setUp()

        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
        ]);

        VendorBillLine::factory()->create([
            'vendor_bill_id' => $bill->id,
            'expense_account_id' => $this->expenseAccount->id,
            'quantity' => 1,
            'unit_price' => 10000, // $100.00
            'subtotal' => 10000,
        ]);

        $bill->refresh();
        $bill->calculateTotalsFromLines();

        Livewire::test(FilamentEditVendorBill::class, ['record' => $bill->getKey()])
            ->callAction('post')
            ->assertHasNoErrors()
            ->assertNotified();

        $bill->refresh();
        $this->assertEquals(VendorBillStatus::Posted, $bill->status);
    }
}
