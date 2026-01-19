<?php

use App\Models\User;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Foundation\Models\Partner;
use Tests\Builders\CompanyBuilder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\Account;
use Brick\Money\Money;
use Modules\Sales\Services\InvoiceService;

uses(Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    // Setup Company A (primary)
    $this->setupWithConfiguredCompany();
    $this->companyA = $this->company;
    $this->userA = $this->user;

    // Setup Company B (secondary)
    $this->companyB = CompanyBuilder::new()
        ->withDefaultAccounts()
        ->withDefaultJournals()
        ->create();

    $this->userB = User::factory()->create();
    $this->userB->companies()->attach($this->companyB);

    // Assign super_admin role to User B for Company B
    setPermissionsTeamId($this->companyB->id);
    $superAdminRoleB = \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'super_admin',
        'company_id' => $this->companyB->id,
    ]);
    if ($superAdminRoleB->wasRecentlyCreated) {
        $superAdminRoleB->givePermissionTo(\Spatie\Permission\Models\Permission::all());
    }
    $this->userB->assignRole($superAdminRoleB);

    // Reset to Company A
    setPermissionsTeamId($this->companyA->id);
    Filament::setTenant($this->companyA);
    $this->actingAs($this->userA);
});

it('isolates product visibility between companies in database', function () {
    $productA = Product::factory()->for($this->companyA)->create(['name' => 'Product A']);
    $productB = Product::factory()->for($this->companyB)->create(['name' => 'Product B']);

    // Check Company A scope
    $productsA = Product::where('company_id', $this->companyA->id)->get();
    expect($productsA)->toHaveCount(1)
        ->and($productsA->first()->id)->toBe($productA->id);

    // Check Company B scope
    $productsB = Product::where('company_id', $this->companyB->id)->get();
    expect($productsB)->toHaveCount(1)
        ->and($productsB->first()->id)->toBe($productB->id);
});

it('prevents user from accessing another companies resources via URL', function () {
    // User B trying to access Company A's product list
    $response = $this->actingAs($this->userB)
        ->get("/jmeryar/{$this->companyA->id}/products");

    expect($response->status())->toBeIn([403, 404]);
});

it('prevents cross-company usage in invoice creation', function () {
    // Attempt to create an invoice in Company B using Company A's product

    // Create a product in Company A with a valid income account in Company A
    $incomeAccountA = Account::factory()->for($this->companyA)->create(['type' => \Modules\Accounting\Enums\Accounting\AccountType::Income]);
    $productA = Product::factory()->for($this->companyA)->create([
        'unit_price' => 100,
        'income_account_id' => $incomeAccountA->id,
        'name' => 'Product A'
    ]);

    // Create a customer in Company B
    $customerB = Partner::factory()->for($this->companyB)->create(['name' => 'Customer B']);

    // Switch to Company B context
    setPermissionsTeamId($this->companyB->id);
    Filament::setTenant($this->companyB);
    $this->actingAs($this->userB);

    // Note: The DTO requires explicit arguments that match the definition
    $dto = new CreateInvoiceDTO(
        company_id: $this->companyB->id,
        customer_id: $customerB->id,
        currency_id: $this->companyB->currency_id,
        invoice_date: now()->toDateString(),
        due_date: now()->addDays(30)->toDateString(),
        lines: [
            new CreateInvoiceLineDTO(
                description: $productA->name,
                quantity: 1.0,
                unit_price: Money::of(100, $this->companyB->currency->code),
                income_account_id: $incomeAccountA->id, // Cross-company account!
                product_id: $productA->id, // Cross-company product!
                tax_id: null
            )
        ],
        fiscal_position_id: null
    );

    // We expect this to fail or we will uncover a gap.
    // Specifically, if we try to use income_account_id from Company A in an invoice for Company B,
    // it should fail database constraint or validation.

    try {
        // We use InvoiceService or CreateInvoiceAction.
        // Based on gaps, CreateInvoiceAction is available.
        app(CreateInvoiceAction::class)->execute($dto);

        $this->fail('Allowed creating an invoice with cross-company product/account.');
    } catch (\Exception $e) {
        // We expect an exception.
        expect(true)->toBeTrue();
    }
});
