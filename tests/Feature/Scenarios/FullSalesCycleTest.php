<?php

namespace Tests\Feature\Scenarios;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Modules\Accounting\Emails\DunningReminderMail;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\DunningLevel;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Product\Models\Product;
use Modules\Sales\Actions\Sales\AcceptQuoteAction;
use Modules\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Modules\Sales\Actions\Sales\ConvertQuoteToSalesOrderAction;
use Modules\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Enums\Sales\QuoteStatus;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\InvoiceService;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class FullSalesCycleTest extends TestCase
{
    use RefreshDatabase;
    use WithConfiguredCompany;

    protected User $user;
    protected Currency $currency;
    protected Partner $customer;
    protected Product $product;
    protected Account $incomeAccount;
    protected Journal $bankJournal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupWithConfiguredCompany();

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);

        $this->currency = $this->company->currency;

        $this->customer = Partner::factory()->create([
            'company_id' => $this->company->id,
            'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
            'email' => 'customer@example.com',
        ]);

        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'income',
            'code' => '4000',
            'name' => 'Sales',
        ]);

        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'income_account_id' => $this->incomeAccount->id,
        ]);

        $this->bankJournal = Journal::factory()->for($this->company)->create([
            'type' => JournalType::Bank,
            'name' => 'Bank',
            'short_code' => 'BNK1'
        ]);

        // Setup Dunning Levels
        DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Reminder 1',
            'days_overdue' => 5,
            'email_subject' => 'URGENT: Payment Reminder',
            'send_email' => true,
        ]);

        DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Reminder 2',
            'days_overdue' => 15,
            'email_subject' => 'FINAL NOTICE',
            'send_email' => true,
        ]);
    }

    public function test_full_sales_cycle_quote_to_dunning_to_payment()
    {
        Mail::fake();
        Carbon::setTestNow('2026-01-01 10:00:00');

        // ==========================================
        // 1. Quote Phase
        // ==========================================
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'partner_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
            'created_by_user_id' => $this->user->id,
        ]);

        QuoteLine::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => Money::of(100, $this->currency->code),
        ]);

        // Send Quote (Required before Accepting)
        app(\Modules\Sales\Actions\Sales\SendQuoteAction::class)->execute($quote);

        // Accept Quote
        app(AcceptQuoteAction::class)->execute($quote, $this->user);

        $this->assertEquals(QuoteStatus::Accepted, $quote->refresh()->status);

        // ==========================================
        // 2. Sales Order Phase
        // ==========================================

        // Convert to Sales Order
        $salesOrder = app(ConvertQuoteToSalesOrderAction::class)->execute($quote);

        $this->assertInstanceOf(SalesOrder::class, $salesOrder);
        $this->assertEquals($quote->quote_number, $salesOrder->reference);
        $this->assertEquals(SalesOrderStatus::Draft, $salesOrder->status); // Assuming default is Draft

        // Confirm Sales Order
        $salesOrder = app(ConfirmSalesOrderAction::class)->execute($salesOrder, $this->user);
        $this->assertEquals(SalesOrderStatus::Confirmed, $salesOrder->status);

        // ==========================================
        // 3. Invoice Phase
        // ==========================================

        $invoiceDto = new CreateInvoiceFromSalesOrderDTO(
            salesOrder: $salesOrder,
            invoice_date: Carbon::now(),
            due_date: Carbon::now()->addDays(30), // Due Feb 1st roughly
            default_income_account_id: $this->incomeAccount->id
        );

        $invoice = app(CreateInvoiceFromSalesOrderAction::class)->execute($invoiceDto);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::Draft, $invoice->status);
        $this->assertEquals($salesOrder->id, $invoice->sales_order_id);

        // Confirm Invoice (Post)
        app(InvoiceService::class)->confirm($invoice, $this->user);
        $invoice->refresh();

        $this->assertEquals(InvoiceStatus::Posted, $invoice->status);
        $this->assertEquals(1000.0, $invoice->total_amount->getAmount()->toFloat()); // 10 * 100

        // ==========================================
        // 4. Dunning Phase (Pre-Payment)
        // ==========================================

        // Fast forward to overdue state
        // Due date is +30 days (approx Jan 31).
        // Level 1 is 5 days overdue. So we need to be at ~Feb 5th.

        $dueDate = Carbon::parse($invoice->due_date);
        Carbon::setTestNow($dueDate->copy()->addDays(6)); // 6 days overdue

        // Run Dunning
        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $invoice->refresh();
        $this->assertEquals(1, $invoice->dunning_level_id, 'Invoice should be at Dunning Level 1');
        $this->assertNotNull($invoice->last_dunning_date);

        Mail::assertSent(DunningReminderMail::class, function ($mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });

        // ==========================================
        // 5. Payment Phase
        // ==========================================

        $paymentAmount = Money::of(1000, $this->currency->code);

        $documentLinks = [
            new CreatePaymentDocumentLinkDTO(
                document_type: 'invoice',
                document_id: $invoice->id,
                amount_applied: $paymentAmount
            )
        ];

        $paymentDto = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->currency->id,
            payment_date: now()->toDateString(),
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            paid_to_from_partner_id: $this->customer->id,
            amount: $paymentAmount,
            document_links: $documentLinks,
            reference: 'Payment for Invoice'
        );

        $payment = app(CreatePaymentAction::class)->execute($paymentDto, $this->user);

        $this->assertEquals(PaymentStatus::Draft, $payment->status);

        // Confirm Payment
        app(PaymentService::class)->confirm($payment, $this->user);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Confirmed, $payment->status);

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status, 'Invoice should be marked as Paid');

        // ==========================================
        // 6. Dunning Phase (Post-Payment)
        // ==========================================

        // Fast forward more time.
        // If it wasn't paid, it would be overdue by 20+ days (Level 2 is 15 days overdue).
        Carbon::setTestNow($dueDate->copy()->addDays(20));

        // Clear mailer to check for new mails
        // Mail::getFacadeRoot()->setGroup('default', []);

        // Run Dunning again
        app(ProcessDunningRunAction::class)->execute($this->company->id);

        $invoice->refresh();

        // Should NOT have upgraded to Level 2 because it is paid.
        // Dunning query filters for 'Posted' and payment_state != 'paid'.
        // Since status is 'Paid' (or Posted + paid), it shouldn't be picked up.
        // Actually, InvoiceStatus::Paid is a separate status from Posted in some systems,
        // or it's Posted + payment_state='paid'.
        // The Service sets status to InvoiceStatus::Paid.

        $this->assertNotEquals(2, $invoice->dunning_level_id, 'Invoice should not advance to Level 2 after payment');
        // It should probably stay at 1 or be cleared. Implementation dependent, but definitely not 2.

        // Also verify no new email sent for this invoice
        // Note: DunningReminderMail might be sent for other tests if DB not refreshed, but we use RefreshDatabase.
        // We cleared mails earlier (in theory, or just check count).
        // Since we are asserting specifically for this invoice/level.

        // Actually, let's just assert the level didn't change to 2.
    }
}
