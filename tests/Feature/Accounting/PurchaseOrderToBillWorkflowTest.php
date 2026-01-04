<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Actions\Purchases\CreatePurchaseOrderAction;
use Modules\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\VendorBillService;

uses(RefreshDatabase::class);

test('Purchase Order to Vendor Bill Workflow (Double Entry Verification)', function () {
    // 1. Setup: Create Company, User, Currency
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $company = Company::firstOrFail();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    // Assign Permissions
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    setPermissionsTeamId($company->id);
    $user->assignRole('super_admin');

    $currency = Currency::where('code', $company->currency->code)->firstOrFail();

    // 2. Setup: Accounts (Asset, Payable, Expense)
    // We need to ensure specific accounts exist for our product mapping
    $payableAccount = Account::where('type', AccountType::Payable)->first();
    if (! $payableAccount) {
        $payableAccount = Account::create([
            'company_id' => $company->id,
            'code' => '2100',
            'name' => 'Accounts Payable',
            'type' => AccountType::Payable,
            'currency_id' => $currency->id,
        ]);
    }

    $expenseAccount = Account::where('type', AccountType::Expense)->first();
    if (! $expenseAccount) {
        $expenseAccount = Account::create([
            'company_id' => $company->id,
            'code' => '6000',
            'name' => 'General Expense',
            'type' => AccountType::Expense,
            'currency_id' => $currency->id,
        ]);
    }

    // Ensure company defaults are set
    $company->update([
        'default_accounts_payable_id' => $payableAccount->id,
        'default_expense_account_id' => $expenseAccount->id,
    ]);

    // 3. Setup: Vendor and Product
    $vendor = Partner::create([
        'company_id' => $company->id,
        'name' => 'Test Vendor',
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
        'is_active' => true,
    ]);

    $product = Product::create([
        'company_id' => $company->id,
        'name' => 'Test Service',
        'sku' => 'TEST-SERVICE-001',
        'type' => ProductType::Service, // Service to keep it simple (no stock moves required for this test ops)
        'expense_account_id' => $expenseAccount->id,
        'sale_price' => 100,
        'cost_price' => 50,
    ]);

    // 4. Action: Create Purchase Order (User Persona)
    $poDto = new CreatePurchaseOrderDTO(
        company_id: $company->id,
        vendor_id: $vendor->id,
        currency_id: $currency->id,
        created_by_user_id: $user->id,
        reference: 'PO-TEST-001',
        po_date: Carbon::now(),
        expected_delivery_date: Carbon::now()->addDays(7),
        exchange_rate_at_creation: 1.0,
        notes: 'Test PO',
        terms_and_conditions: null,
        delivery_location_id: null,
        lines: [
            new CreatePurchaseOrderLineDTO(
                product_id: $product->id,
                description: 'Test Line Item',
                quantity: 10,
                unit_price: \Brick\Money\Money::of(50, $currency->code), // Total 500
                tax_id: null,
                expected_delivery_date: null
            ),
        ]
    );

    $createAction = app(CreatePurchaseOrderAction::class);
    $po = $createAction->execute($poDto);

    expect($po)->toBeInstanceOf(PurchaseOrder::class)
        ->status->toBe(PurchaseOrderStatus::Draft);

    expect($po->total_amount->getAmount()->toFloat())->toBe(500.00);

    // 5. Action: Confirm PO (User Persona)
    $poService = app(PurchaseOrderService::class);
    $poService->confirm($po, $user);

    expect($po->refresh())->status->toBe(PurchaseOrderStatus::ToReceive);

    // 6. Action: Create Vendor Bill from PO (User Persona)
    $billDto = new CreateVendorBillFromPurchaseOrderDTO(
        bill_reference: 'BILL-TEST-001',
        bill_date: Carbon::now(),
        accounting_date: Carbon::now(),
        due_date: Carbon::now()->addDays(30),
        created_by_user_id: $user->id,
        purchase_order_id: $po->id,
        line_quantities: [$po->lines->first()->id => 10], // Full billing
        payment_term_id: null
    );

    $createBillAction = app(CreateVendorBillFromPurchaseOrderAction::class);
    $bill = $createBillAction->execute($billDto);

    expect($bill)->toBeInstanceOf(VendorBill::class)
        ->status->toBe(VendorBillStatus::Draft);

    expect($bill->total_amount->getAmount()->toFloat())->toBe(500.00);

    // 7. Action: Post Vendor Bill (Accountant Persona)
    $billService = app(VendorBillService::class);

    // Mock user permissions if necessary, or ensure user has permissions.
    // Usually admin/factory user might need permissions, but let's assume no strict gate/policy for now or that admin has it.
    // If it fails with AuthorizationException, we can check.
    $billService->post($bill, $user);

    expect($bill->refresh())
        ->status->toBe(VendorBillStatus::Posted)
        ->journal_entry_id->not->toBeNull();

    // 8. Verification: Check Journal Entry (Accountant Persona)
    $je = JournalEntry::with('lines.account')->find($bill->journal_entry_id);

    expect($je)->not->toBeNull()
        ->is_posted->toBeTrue();

    expect($je->total_debit->getAmount()->toFloat())->toBe(500.00);
    expect($je->total_credit->getAmount()->toFloat())->toBe(500.00);

    // Verify Lines
    $debitLine = $je->lines->first(fn ($l) => $l->debit->getAmount()->toFloat() > 0);
    $creditLine = $je->lines->first(fn ($l) => $l->credit->getAmount()->toFloat() > 0);

    // Debit should be Expense Account
    expect($debitLine->account_id)->toBe($expenseAccount->id);
    expect($debitLine->debit->getAmount()->toFloat())->toBe(500.00);

    // Credit should be Accounts Payable
    expect($creditLine->account_id)->toBe($payableAccount->id);
    expect($creditLine->credit->getAmount()->toFloat())->toBe(500.00);

    // Verify Hash integrity (Basic check)
    expect($je->hash)->not->toBeNull();
});
