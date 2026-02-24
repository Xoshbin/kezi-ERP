<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Pos\Actions\CreatePosReturnAction;
use Kezi\Pos\DataTransferObjects\CreatePosReturnDTO;
use Kezi\Pos\DataTransferObjects\CreatePosReturnLineDTO;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\Pages\ListPosReturns;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\Pages\ViewPosReturn;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns\PosReturnResource;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

/**
 * Helper to create a complete POS return in draft state.
 *
 * @return array{return: PosReturn, order: PosOrder, user: \App\Models\User}
 */
function createDraftReturn(): array
{
    $testState = test();
    /** @var \Tests\Traits\WithConfiguredCompany $testState */
    $company = $testState->company;
    $user = $testState->user;

    $currency = Currency::where('code', 'USD')->first()
        ?? Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

    $company->update(['currency_id' => $currency->id]);

    // Ensure required accounts and journals
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
        'return_policy' => ['enabled' => true],
        'stock_location_id' => StockLocation::factory()->create(['company_id' => $company->id])->id,
    ]);

    $session = PosSession::factory()->create([
        'company_id' => $company->id,
        'pos_profile_id' => $profile->id,
        'user_id' => $user->id,
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
        requested_by_user_id: $user->id,
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
                return_reason_line: 'No longer needed',
                metadata: null,
            ),
        ],
    );

    $posReturn = app(CreatePosReturnAction::class)->execute($dto);

    return ['return' => $posReturn, 'order' => $order, 'user' => $user];
}

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setCurrentPanel(Filament::getPanel('kezi'));
});

it('can render the pos returns list page', function () {
    $this->get(PosReturnResource::getUrl('index'))
        ->assertSuccessful();
});

it('can see pos returns in the table', function () {
    ['return' => $posReturn] = createDraftReturn();

    livewire(ListPosReturns::class)
        ->assertCanSeeTableRecords([$posReturn]);
});

it('can filter pos returns by status', function () {
    ['return' => $draftReturn] = createDraftReturn();

    $draftReturn->update(['status' => PosReturnStatus::Approved]);

    livewire(ListPosReturns::class)
        ->filterTable('status', PosReturnStatus::Approved->value)
        ->assertCanSeeTableRecords([$draftReturn]);
});

it('can view a pos return detail page', function () {
    ['return' => $posReturn] = createDraftReturn();

    $this->get(PosReturnResource::getUrl('view', ['record' => $posReturn]))
        ->assertSuccessful();
});

it('shows the approve action only for pending approval returns', function () {
    ['return' => $posReturn] = createDraftReturn();

    // Draft — approve button should NOT be visible
    livewire(ViewPosReturn::class, ['record' => $posReturn->getKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject')
        ->assertActionHidden('process');

    // Move to pending_approval
    $posReturn->update(['status' => PosReturnStatus::PendingApproval]);

    livewire(ViewPosReturn::class, ['record' => $posReturn->getKey()])
        ->assertActionVisible('approve')
        ->assertActionVisible('reject');
});

it('shows the process action only for approved returns', function () {
    ['return' => $posReturn] = createDraftReturn();

    $posReturn->update(['status' => PosReturnStatus::Approved]);

    livewire(ViewPosReturn::class, ['record' => $posReturn->getKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('reject')
        ->assertActionVisible('process');
});

it('can approve a pending return from the view page', function () {
    ['return' => $posReturn] = createDraftReturn();
    $posReturn->update(['status' => PosReturnStatus::PendingApproval]);

    livewire(ViewPosReturn::class, ['record' => $posReturn->getKey()])
        ->callAction('approve')
        ->assertHasNoActionErrors();

    expect($posReturn->fresh()->status)->toBe(PosReturnStatus::Approved);
    expect($posReturn->fresh()->approved_by_user_id)->toBe($this->user->id);
});

it('can reject a pending return with a reason', function () {
    ['return' => $posReturn] = createDraftReturn();
    $posReturn->update(['status' => PosReturnStatus::PendingApproval]);

    livewire(ViewPosReturn::class, ['record' => $posReturn->getKey()])
        ->callAction('reject', data: ['reason' => 'Item is not in returnable condition.'])
        ->assertHasNoActionErrors();

    $fresh = $posReturn->fresh();
    expect($fresh->status)->toBe(PosReturnStatus::Rejected);
    expect($fresh->return_notes)->toContain('Item is not in returnable condition.');
});
