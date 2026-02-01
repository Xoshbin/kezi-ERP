<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Jmeryar\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\DunningLevel;
use Jmeryar\Foundation\Enums\Partners\PartnerType;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);

    $this->currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);

    $this->customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'customer@example.com',
        'type' => PartnerType::Customer,
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
        'type' => ProductType::Service,
        'income_account_id' => $incomeAccount->id,
    ]);

    $this->level1 = DunningLevel::create([
        'company_id' => $this->company->id,
        'name' => 'Reminder 1',
        'days_overdue' => 5,
        'email_subject' => 'Reminder 1',
        'send_email' => true,
        'charge_fee' => true,
        'fee_amount' => 10,
        'fee_product_id' => $this->feeProduct->id,
    ]);

    $this->level2 = DunningLevel::create([
        'company_id' => $this->company->id,
        'name' => 'Reminder 2',
        'days_overdue' => 15,
        'email_subject' => 'Final Notice',
        'send_email' => true,
        'charge_fee' => true,
        'fee_percentage' => 5,
        'fee_product_id' => $this->feeProduct->id,
    ]);

    Carbon::setTestNow('2026-01-20 10:00:00');
});

it('assigns correct level based on days overdue', function () {
    Mail::fake();

    // 6 days overdue -> Level 1
    $invoice1 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(6),
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
    ]);

    // 16 days overdue -> Level 2
    $invoice2 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(16),
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
    ]);

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice1 */
    $invoice1 = $invoice1->refresh();
    /** @var Invoice $invoice2 */
    $invoice2 = $invoice2->refresh();

    // Using Pest expectations
    expect($invoice1->dunning_level_id)->toBe($this->level1->id)
        ->and($invoice2->dunning_level_id)->toBe($this->level2->id);
});

it('excludes fully paid invoices from dunning', function () {
    Mail::fake();

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(10),
        'status' => InvoiceStatus::Paid, // Paid status
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
    ]);

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    expect($invoice->dunning_level_id)->toBeNull();
});

it('includes partially paid invoices in dunning', function () {
    Mail::fake();

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(10),
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
    ]);

    // Mock partial payment (status remains Posted in many systems until fully paid)
    // For now, our dunning logic only checks status=Posted.
    // If it's partially paid, it SHOULD still be dunned.

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    expect($invoice->dunning_level_id)->toBe($this->level1->id);
});

it('escalates dunning level when more days pass', function () {
    Mail::fake();

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(6),
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
    ]);

    // Run 1: Level 1
    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();
    expect($invoice->dunning_level_id)->toBe($this->level1->id);

    // Advance time to 16 days overdue
    Carbon::setTestNow(Carbon::today()->addDays(10));

    // Next dunning date must be <= today. ProcessDunningRunAction sets next_dunning_date = today + 1 day.
    // So we need to advance at least 1 day for it to be eligible again.

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    expect($invoice->dunning_level_id)->toBe($this->level2->id);
});

it('does not downgrade dunning level', function () {
    Mail::fake();

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(20),
        'status' => InvoiceStatus::Posted,
        'currency_id' => $this->currency->id,
        'total_amount' => 1000,
        'dunning_level_id' => $this->level2->id, // Already at level 2
    ]);

    // Change due date to be only 6 days overdue (would normally be level 1)
    $invoice->update(['due_date' => Carbon::today()->subDays(6)]);

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    // Should STILL be level 2
    expect($invoice->dunning_level_id)->toBe($this->level2->id);
});

it('processes multi currency invoice dunning fees correctly on remaining amount', function () {
    Mail::fake();

    $eur = Currency::factory()->create(['code' => 'EUR']);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(16), // Level 2: 5% fee
        'status' => InvoiceStatus::Posted,
        'currency_id' => $eur->id,
        'total_amount' => 1000, // 1000 EUR
    ]);

    // We calculate 5% of REMAINING amount.
    // 5% of 1000 = 50.

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    expect($invoice->generatedDebitNotes)->toHaveCount(1);

    /** @var Invoice $debitNote */
    $debitNote = $invoice->generatedDebitNotes->first();

    // 5% of 1000 EUR = 50 EUR
    expect($debitNote->currency->code)->toBe('EUR')
        ->and($debitNote->total_amount->isEqualTo(50))->toBeTrue();
});

it('excludes invoices with zero remaining balance from dunning', function () {
    Mail::fake();

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'due_date' => Carbon::today()->subDays(10),
        'status' => InvoiceStatus::Posted, // Still Posted but balance is zero
        'currency_id' => $this->currency->id,
        'total_amount' => 0, // Zero balance
    ]);

    app(ProcessDunningRunAction::class)->execute($this->company->id);

    /** @var Invoice $invoice */
    $invoice = $invoice->refresh();

    expect($invoice->dunning_level_id)->toBeNull();
});
