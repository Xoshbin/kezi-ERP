<?php

namespace Jmeryar\Accounting\Tests\Feature\Dunning;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Jmeryar\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\DunningLevel;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;
use Tests\TestCase;

class DunningFeeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Partner $customer;

    protected Product $feeProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        $this->currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

        $this->customer = Partner::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
            'type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Customer,
        ]);

        $incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Fees Income',
            'code' => '400000',
            'type' => \Jmeryar\Accounting\Enums\Accounting\AccountType::Income,
        ]);

        $this->feeProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Late Payment Fee',
            'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
            'income_account_id' => $incomeAccount->id,
        ]);

        Carbon::setTestNow('2026-01-12 10:00:00');
    }

    public function test_it_creates_debit_note_when_dunning_level_has_fixed_fee()
    {
        Mail::fake();

        // Level with $50 fee
        $level = DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Level 1 Fee',
            'days_overdue' => 10,
            'charge_fee' => true,
            'fee_amount' => 50,
            'fee_product_id' => $this->feeProduct->id,
            'send_email' => false,
        ]);

        // Overdue Invoice
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(11),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 1000,
        ]);

        // Run Action
        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $invoice->refresh();

        // Assert Level Applied
        $this->assertEquals($level->id, $invoice->dunning_level_id);

        // Assert Debit Note Created
        $this->assertCount(1, $invoice->generatedDebitNotes);
        $debitNote = $invoice->generatedDebitNotes->first();

        // Assert Linkage
        $this->assertEquals($invoice->id, $debitNote->source_invoice_id);

        // Assert Details
        $this->assertEquals(InvoiceStatus::Draft, $debitNote->status);
        $this->assertEquals($this->customer->id, $debitNote->customer_id);

        // Assert Fee Amount (50)
        // Adjust assertion based on Money object handling. Assuming total_amount is cast to Money.
        $this->assertTrue($debitNote->total_amount->isEqualTo(50));

        // Assert Line Item
        $this->assertEquals(1, $debitNote->invoiceLines->count());
        $line = $debitNote->invoiceLines->first();
        $this->assertEquals($this->feeProduct->id, $line->product_id);
        $this->assertEquals('Late Fee: Level 1 Fee', $line->description);
    }

    public function test_it_creates_debit_note_when_dunning_level_has_percentage_fee()
    {
        Mail::fake();

        // Level with 10% fee
        $level = DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Level 2 % Fee',
            'days_overdue' => 10,
            'charge_fee' => true,
            'fee_percentage' => 10,
            'fee_product_id' => $this->feeProduct->id,
            'send_email' => false,
        ]);

        // Overdue Invoice $1000
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(11),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 1000,
        ]);

        // Run Action
        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $invoice->refresh();
        $this->assertCount(1, $invoice->generatedDebitNotes);
        $debitNote = $invoice->generatedDebitNotes->first();

        // 10% of 1000 = 100
        $this->assertTrue($debitNote->total_amount->isEqualTo(100));
    }

    public function test_it_combines_fixed_and_percentage_fees()
    {
        Mail::fake();

        // Level with $10 + 5% fee
        DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Combo Fee',
            'days_overdue' => 10,
            'charge_fee' => true,
            'fee_amount' => 10,
            'fee_percentage' => 5,
            'fee_product_id' => $this->feeProduct->id,
            'send_email' => false,
        ]);

        // Overdue Invoice $1000
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(11),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 1000,
            'invoice_number' => 'INV-COMBO-1',
        ]);

        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $dateHelper = Invoice::where('invoice_number', 'INV-COMBO-1')->first();
        $debitNote = $dateHelper->generatedDebitNotes->first();

        // 10 + (5% of 1000 = 50) = 60
        $this->assertTrue($debitNote->total_amount->isEqualTo(60));
    }

    public function test_it_does_not_create_debit_note_if_charge_fee_is_false()
    {
        Mail::fake();

        // Level with fee configured but charge_fee = false
        DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'No Charge',
            'days_overdue' => 10,
            'charge_fee' => false,
            'fee_amount' => 50,
            'fee_product_id' => $this->feeProduct->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(11),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 1000,
        ]);

        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $invoice->refresh();
        $this->assertCount(0, $invoice->generatedDebitNotes);
    }
}
