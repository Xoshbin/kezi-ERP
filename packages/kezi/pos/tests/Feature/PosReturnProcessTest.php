<?php

namespace Kezi\Pos\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Actions\CreatePosReturnAction;
use Kezi\Pos\Actions\ProcessPosReturnAction;
use Kezi\Pos\DataTransferObjects\CreatePosReturnDTO;
use Kezi\Pos\DataTransferObjects\CreatePosReturnLineDTO;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Tests\TestCase;

class PosReturnProcessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Need some basic data like Journals and Accounts
    }

    public function test_can_create_and_process_return()
    {
        // 1. Setup Data
        $currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD', 'is_active' => true]);

        $receivableAccount = \Kezi\Accounting\Models\Account::factory()->create([
            'company_id' => 1, // Will be updated
            'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Receivable,
        ]);

        $incomeAccount = \Kezi\Accounting\Models\Account::factory()->create([
            'company_id' => 1,
            'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Income,
        ]);

        $company = \App\Models\Company::factory()->create([
            'currency_id' => $currency->id,
            'default_accounts_receivable_id' => $receivableAccount->id,
            'default_stock_location_id' => \Kezi\Inventory\Models\StockLocation::factory()->create()->id,
        ]);

        $receivableAccount->update(['company_id' => $company->id]);
        $incomeAccount->update(['company_id' => $company->id]);

        $user = User::factory()->create();
        $user->companies()->attach($company);

        $this->actingAs($user);

        // Ensure journals exist
        $salesJournal = \Kezi\Accounting\Models\Journal::factory()->create([
            'company_id' => $company->id,
            'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Sale,
        ]);

        $company->update(['default_sales_journal_id' => $salesJournal->id]);

        $purchaseJournal = \Kezi\Accounting\Models\Journal::factory()->create([
            'company_id' => $company->id,
            'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Purchase,
        ]);
        $company->update(['default_purchase_journal_id' => $purchaseJournal->id]);

        $paymentJournal = \Kezi\Accounting\Models\Journal::factory()->create([
            'company_id' => $company->id,
            'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
        ]);

        $profile = PosProfile::factory()->create([
            'company_id' => $company->id,
            'default_income_account_id' => \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $company->id])->id,
            'default_payment_journal_id' => $paymentJournal->id,
            'return_policy' => ['enabled' => true],
            'stock_location_id' => \Kezi\Inventory\Models\StockLocation::factory()->create(['company_id' => $company->id])->id,
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
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
            'unit_price' => 1000,
            'average_cost' => 800,
        ]);

        $orderLine = PosOrderLine::factory()->create([
            'pos_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'total_amount' => 2000,
        ]);

        // 2. Create Return DTO
        $lineDto = new CreatePosReturnLineDTO(
            original_order_line_id: $orderLine->id,
            product_id: $orderLine->product_id,
            quantity_returned: 1.0,
            quantity_available: 2.0,
            unit_price: 1000,
            refund_amount: 1000,
            restocking_fee_line: 0,
            restock: true,
            item_condition: 'good',
            return_reason_line: 'Customer changed mind',
            metadata: null
        );

        $dto = new CreatePosReturnDTO(
            company_id: $company->id,
            pos_session_id: $session->id,
            original_order_id: $order->id,
            currency_id: $currency->id,
            return_date: now(),
            return_reason: 'Normal return',
            return_notes: 'Some notes',
            requested_by_user_id: $user->id,
            refund_method: 'cash',
            lines: [$lineDto]
        );

        // 3. Execute Create Action
        /** @var CreatePosReturnAction $createAction */
        $createAction = app(CreatePosReturnAction::class);
        $return = $createAction->execute($dto);

        $this->assertDatabaseHas('pos_returns', [
            'id' => $return->id,
            'status' => PosReturnStatus::Draft->value,
            'refund_amount' => 100000,
        ]);

        // 4. Approve Return
        $return->update(['status' => PosReturnStatus::Approved]);

        // 5. Execute Process Action
        /** @var ProcessPosReturnAction $processAction */
        $processAction = app(ProcessPosReturnAction::class);
        $return = $processAction->execute($return, $user);

        $this->assertEquals(PosReturnStatus::Completed, $return->status);
        $this->assertNotNull($return->credit_note_id);
        $this->assertNotNull($return->payment_reversal_id);
        $this->assertNotNull($return->stock_move_id);

        // Verify invoice status
        $this->assertEquals(\Kezi\Sales\Enums\Sales\InvoiceStatus::Paid, $return->creditNote->status);

        // Verify payment status
        $this->assertEquals(\Kezi\Payment\Enums\Payments\PaymentStatus::Confirmed, $return->paymentReversal->status);

        // Verify stock move status
        $this->assertEquals(\Kezi\Inventory\Enums\Inventory\StockMoveStatus::Done, $return->stockMove->status);
    }
}
