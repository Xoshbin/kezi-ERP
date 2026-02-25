<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Pos\Actions\CreatePosReturnAction;
use Kezi\Pos\DataTransferObjects\CreatePosReturnDTO;
use Kezi\Pos\DataTransferObjects\CreatePosReturnLineDTO;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/**
 * Create a PosReturn in PendingApproval state together with a manager user.
 *
 * @return array{return: PosReturn, cashier: \App\Models\User, manager: \App\Models\User}
 */
function createPendingApprovalReturn(): array
{
    /** @var \Tests\Traits\WithConfiguredCompany $test */
    $test = test();
    $company = $test->company;
    $cashier = $test->user;

    $currency = Currency::where('code', 'USD')->first()
        ?? Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    $company->update(['currency_id' => $currency->id]);

    $receivableAccount = \Kezi\Accounting\Models\Account::factory()->create([
        'company_id' => $company->id,
        'type' => AccountType::Receivable,
    ]);
    $company->update(['default_accounts_receivable_id' => $receivableAccount->id]);

    $salesJournal = \Kezi\Accounting\Models\Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Sale,
    ]);
    $company->update(['default_sales_journal_id' => $salesJournal->id]);

    $purchaseJournal = \Kezi\Accounting\Models\Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Purchase,
    ]);
    $company->update(['default_purchase_journal_id' => $purchaseJournal->id]);

    $paymentJournal = \Kezi\Accounting\Models\Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Bank,
    ]);

    $stockLocation = StockLocation::factory()->create(['company_id' => $company->id]);
    $company->update(['default_stock_location_id' => $stockLocation->id]);

    $profile = PosProfile::factory()->create([
        'company_id' => $company->id,
        'default_income_account_id' => \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $company->id])->id,
        'default_payment_journal_id' => $paymentJournal->id,
        'stock_location_id' => StockLocation::factory()->create(['company_id' => $company->id])->id,
        'return_policy' => ['enabled' => true, 'require_manager_approval' => true],
    ]);

    $session = PosSession::factory()->create([
        'company_id' => $company->id,
        'pos_profile_id' => $profile->id,
        'user_id' => $cashier->id,
    ]);

    $order = PosOrder::factory()->create([
        'company_id' => $company->id,
        'pos_session_id' => $session->id,
        'currency_id' => $currency->id,
        'status' => 'paid',
    ]);

    $product = \Kezi\Product\Models\Product::factory()->create([
        'company_id' => $company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Service,
    ]);

    $line = PosOrderLine::factory()->create([
        'pos_order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 500,
        'total_amount' => 1000,
    ]);

    $dto = new CreatePosReturnDTO(
        company_id: $company->id,
        pos_session_id: $session->id,
        original_order_id: $order->id,
        currency_id: $currency->id,
        return_date: now(),
        return_reason: 'Test return',
        return_notes: null,
        requested_by_user_id: $cashier->id,
        refund_method: 'cash',
        lines: [
            new CreatePosReturnLineDTO(
                original_order_line_id: $line->id,
                product_id: $product->id,
                quantity_returned: 1.0,
                quantity_available: 2.0,
                unit_price: 500,
                refund_amount: 500,
                restocking_fee_line: 0,
                restock: false,
                item_condition: 'good',
                return_reason_line: 'Needs manager approval',
                metadata: null,
            ),
        ],
    );

    $posReturn = app(CreatePosReturnAction::class)->execute($dto);
    $posReturn->update(['status' => PosReturnStatus::PendingApproval]);

    // Create a manager in the same company with a known PIN
    $manager = \App\Models\User::factory()->create([
        'pos_manager_pin' => Hash::make('1234'),
    ]);
    $manager->companies()->attach($company->id);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_return', 'web');
    setPermissionsTeamId($company->id);
    $cashier->givePermissionTo('create_pos_return');

    return [
        'return' => $posReturn->fresh(),
        'cashier' => $cashier,
        'manager' => $manager,
    ];
}

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('approves a pending return when a correct manager PIN is provided', function () {
    ['return' => $posReturn, 'cashier' => $cashier] = createPendingApprovalReturn();

    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", ['pin' => '1234'])
        ->assertOk()
        ->assertJsonFragment(['approved' => true]);

    expect($posReturn->fresh()->status)->toBe(PosReturnStatus::Approved);
});

it('rejects the request when the PIN is incorrect', function () {
    ['return' => $posReturn, 'cashier' => $cashier] = createPendingApprovalReturn();

    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", ['pin' => '9999'])
        ->assertStatus(422)
        ->assertJsonFragment(['approved' => false]);

    // Status should remain pending_approval
    expect($posReturn->fresh()->status)->toBe(PosReturnStatus::PendingApproval);
});

it('validates the PIN format — PIN must be digits between 4 and 8 characters', function () {
    ['return' => $posReturn, 'cashier' => $cashier] = createPendingApprovalReturn();

    // Too short
    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", ['pin' => '12'])
        ->assertStatus(422);

    // Missing PIN
    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", [])
        ->assertStatus(422);
});

it('rejects approval when the return is already in an approved state', function () {
    ['return' => $posReturn, 'cashier' => $cashier] = createPendingApprovalReturn();

    // Move to approved
    $posReturn->update(['status' => PosReturnStatus::Approved]);

    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", ['pin' => '1234'])
        ->assertStatus(422)
        ->assertJsonFragment(['approved' => false]);
});

it('does not allow the cashier to approve their own return using their own PIN', function () {
    ['return' => $posReturn, 'cashier' => $cashier] = createPendingApprovalReturn();

    // Give the cashier a PIN too — they should NOT be able to approve their own return
    $cashier->update(['pos_manager_pin' => Hash::make('5678')]);

    $this->actingAs($cashier)
        ->postJson("/api/pos/returns/{$posReturn->id}/verify-pin", ['pin' => '5678'])
        ->assertStatus(422)
        ->assertJsonFragment(['approved' => false]);
});
