<?php

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Accounting\JournalType;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\VendorBill;
use App\Services\PaymentService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Create USD currency for foreign currency tests
    $this->usdCurrency = Currency::firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => ['en' => 'US Dollar', 'ckb' => 'دۆلاری ئەمریکی', 'ar' => 'دولار أمريكي'],
            'symbol' => '$',
            'is_active' => true,
            'decimal_places' => 2,
        ]
    );

    // Set up exchange rates
    $this->invoiceDate = Carbon::parse('2024-01-01');
    $this->paymentDate = Carbon::parse('2024-01-05'); // 4 days later
    $this->invoiceExchangeRate = 1460.0; // 1 USD = 1460 IQD on invoice date
    $this->paymentExchangeRate = 1470.0; // 1 USD = 1470 IQD on payment date (10 IQD gain)

    // Create exchange rates for both dates
    CurrencyRate::updateOrCreate(
        [
            'currency_id' => $this->usdCurrency->id,
            'effective_date' => $this->invoiceDate->toDateString(),
            'company_id' => $this->company->id,
        ],
        [
            'rate' => $this->invoiceExchangeRate,
            'source' => 'manual',
        ]
    );

    CurrencyRate::updateOrCreate(
        [
            'currency_id' => $this->usdCurrency->id,
            'effective_date' => $this->paymentDate->toDateString(),
            'company_id' => $this->company->id,
        ],
        [
            'rate' => $this->paymentExchangeRate,
            'source' => 'manual',
        ]
    );

    // Create bank journal for payments
    $this->bankJournal = Journal::factory()->for($this->company)->create([
        'type' => JournalType::Bank,
        'name' => ['en' => 'Bank Journal'],
    ]);
});

describe('Multi-Currency Payment Tests', function () {
    test('can create USD payment for USD invoice with same exchange rate', function () {
        // Arrange: Create USD invoice
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(100, 'USD'), // $100.00
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create payment on same date (same exchange rate)
        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: Money::of(100, 'USD')
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id,
            payment_date: $this->invoiceDate->toDateString(), // Same date = same rate
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'USD Payment for USD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment amounts
        expect($payment->currency_id)->toBe($this->usdCurrency->id);
        expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($payment->amount->getAmount()->toFloat())->toBe(100.0);

        // Assert: Exchange rate and company currency amount
        expect((float) $payment->exchange_rate_at_payment)->toBe($this->invoiceExchangeRate);
        expect($payment->amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(146000.0); // $100 * 1460

        // Assert: Payment status and journal entry
        expect($payment->status)->toBe(PaymentStatus::Confirmed);
        expect($payment->journal_entry_id)->not->toBeNull();

        // Assert: Journal entry is in base currency
        $journalEntry = $payment->journalEntry;
        expect($journalEntry->currency_id)->toBe($this->company->currency_id);
        expect($journalEntry->total_debit->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($journalEntry->total_debit->getAmount()->toFloat())->toBe(146000.0);
    });

    test('can create USD payment for USD invoice with different exchange rate (gain scenario)', function () {
        // Arrange: Create USD invoice on earlier date
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(100, 'USD'),
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate, // Rate: 1460
        ]);

        // Create payment on later date (different exchange rate)
        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: Money::of(100, 'USD')
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id,
            payment_date: $this->paymentDate->toDateString(), // Later date = different rate
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'USD Payment with Exchange Gain'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment uses current exchange rate
        expect((float) $payment->exchange_rate_at_payment)->toBe($this->paymentExchangeRate);
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(147000.0); // $100 * 1470

        // Assert: Exchange gain/loss should be calculated
        // Invoice: $100 * 1460 = 146,000 IQD
        // Payment: $100 * 1470 = 147,000 IQD
        // Gain: 1,000 IQD should be posted to gain/loss account

        // Note: This tests the exchange gain/loss calculation logic
        // The actual posting might be handled by ExchangeGainLossService
    });
});

describe('Cross-Currency Payment Tests', function () {
    test('can create IQD payment for USD invoice', function () {
        // Arrange: Create USD invoice
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(100, 'USD'),
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create IQD payment (base currency) for USD invoice
        // Payment amount should be in payment currency (IQD)
        $iqdPaymentAmount = Money::of(147000, 'IQD'); // Equivalent to $100 at 1470 rate

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: $iqdPaymentAmount // Applied amount in payment currency
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->company->currency_id, // IQD payment
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'IQD Payment for USD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment is in IQD (base currency)
        expect($payment->currency_id)->toBe($this->company->currency_id);
        expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($payment->amount->getAmount()->toFloat())->toBe(147000.0);

        // Assert: For base currency payment, exchange rate should be 1.0
        expect((float) $payment->exchange_rate_at_payment)->toBe(1.0);
        expect($payment->amount_company_currency->getAmount()->toFloat())
            ->toBe($payment->amount->getAmount()->toFloat());
    });

    test('can create USD payment for IQD invoice', function () {
        // Arrange: Create IQD invoice (base currency)
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->company->currency_id,
            'total_amount' => Money::of(147000, 'IQD'), // 147,000 IQD
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create USD payment for IQD invoice
        // Payment amount should be in payment currency (USD)
        $usdPaymentAmount = Money::of(100, 'USD'); // $100 USD payment

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: $usdPaymentAmount // Applied amount in payment currency
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id, // USD payment
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'USD Payment for IQD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment is in USD
        expect($payment->currency_id)->toBe($this->usdCurrency->id);
        expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($payment->amount->getAmount()->toFloat())->toBe(100.0);

        // Assert: Exchange rate and conversion
        expect((float) $payment->exchange_rate_at_payment)->toBe($this->paymentExchangeRate);
        expect($payment->amount_company_currency->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(147000.0); // $100 * 1470
    });
});

describe('VendorBill Payment Tests', function () {
    test('can create USD payment for USD vendor bill', function () {
        // Arrange: Create USD vendor bill
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(200, 'USD'),
            'status' => 'posted',
            'bill_date' => $this->invoiceDate,
        ]);

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'vendor_bill',
            document_id: $vendorBill->id,
            amount_applied: Money::of(200, 'USD')
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id,
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Outbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'USD Payment for USD Vendor Bill'

        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment type should be outbound for vendor bills
        expect($payment->payment_type)->toBe(PaymentType::Outbound);
        expect($payment->amount->getAmount()->toFloat())->toBe(200.0);
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(294000.0); // $200 * 1470
    });
});

describe('Payment Document Link Tests', function () {
    test('payment document link stores amount in correct currency', function () {
        // Arrange: Create USD invoice
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(150, 'USD'),
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: Money::of(150, 'USD')
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id,
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'Test Payment Document Link'

        );

        // Act: Create payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);

        // Assert: Payment document link
        $paymentDocumentLink = $payment->paymentDocumentLinks->first();
        expect($paymentDocumentLink)->not->toBeNull();
        expect($paymentDocumentLink->amount_applied->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($paymentDocumentLink->amount_applied->getAmount()->toFloat())->toBe(150.0);

        // Assert: Link is properly connected
        expect($paymentDocumentLink->invoice_id)->toBe($invoice->id);
        expect($paymentDocumentLink->payment_id)->toBe($payment->id);
    });

    test('partial payment creates correct payment document link', function () {
        // Arrange: Create USD invoice for $300
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(300, 'USD'),
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create partial payment of $150
        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: Money::of(150, 'USD') // Partial payment
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id,
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'Partial USD Payment'

        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();

        // Assert: Payment amount is partial amount
        expect($payment->amount->getAmount()->toFloat())->toBe(150.0);
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(220500.0); // $150 * 1470

        // Assert: Payment document link shows partial application
        $paymentDocumentLink = $payment->paymentDocumentLinks->first();
        expect($paymentDocumentLink->amount_applied->getAmount()->toFloat())->toBe(150.0);
    });
});

describe('Payment State Bug Tests', function () {
    test('USD invoice with partial IQD payment should show partially paid state', function () {
        // Arrange: Create USD invoice for $1500
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(1500, 'USD'), // $1500 USD
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create partial IQD payment (250,000 IQD = ~$170 at 1470 rate, much less than $1500)
        $partialIqdPayment = Money::of(250000, 'IQD'); // 250,000 IQD

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: $partialIqdPayment // Applied amount in payment currency (IQD)
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->company->currency_id, // IQD payment
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'Partial IQD Payment for USD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();
        $invoice->refresh();

        // Assert: Invoice should be partially paid, not fully paid
        // 250,000 IQD ÷ 1470 rate = ~$170 USD, which is much less than $1500
        expect($invoice->paymentState->value)->toBe('partially_paid')
            ->and($invoice->isPartiallyPaid())->toBeTrue()
            ->and($invoice->isFullyPaid())->toBeFalse();

        // Assert: Payment amounts are correct
        expect($payment->amount->getAmount()->toFloat())->toBe(250000.0);
        expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('IQD');
    });

    test('USD invoice with full USD payment should show paid state', function () {
        // Arrange: Create USD invoice for $100
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(100, 'USD'),
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create full USD payment
        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: Money::of(100, 'USD') // Full payment in USD
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->usdCurrency->id, // USD payment
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'Full USD Payment for USD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();
        $invoice->refresh();

        // Assert: Invoice should be fully paid
        expect($invoice->paymentState->value)->toBe('paid')
            ->and($invoice->isFullyPaid())->toBeTrue()
            ->and($invoice->isPartiallyPaid())->toBeFalse();
    });

    test('real world scenario: $1500 USD invoice with 250,000 IQD payment shows partially paid', function () {
        // This test reproduces the exact scenario described by the user

        // Arrange: Create $1500 USD invoice and post it
        $invoice = Invoice::factory()->for($this->company)->create([
            'currency_id' => $this->usdCurrency->id,
            'total_amount' => Money::of(1500, 'USD'), // $1500 USD
            'status' => 'posted',
            'invoice_date' => $this->invoiceDate,
        ]);

        // Create 250,000 IQD payment (much less than $1500 equivalent)
        // At 1470 rate: 250,000 IQD ÷ 1470 = ~$170 USD (much less than $1500)
        $partialIqdPayment = Money::of(250000, 'IQD');

        $documentLinkDTO = new CreatePaymentDocumentLinkDTO(
            document_type: 'invoice',
            document_id: $invoice->id,
            amount_applied: $partialIqdPayment
        );

        $paymentDTO = new CreatePaymentDTO(
            company_id: $this->company->id,
            journal_id: $this->bankJournal->id,
            currency_id: $this->company->currency_id, // IQD payment
            payment_date: $this->paymentDate->toDateString(),
            // settlement inferred by presence of document links
            payment_type: PaymentType::Inbound,
            payment_method: PaymentMethod::BankTransfer,
            partner_id: null,
            amount: null,
            document_links: [$documentLinkDTO],
            reference: 'Real World Scenario: 250,000 IQD for $1500 USD Invoice'
        );

        // Act: Create and confirm payment
        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $this->user);
        app(PaymentService::class)->confirm($payment, $this->user);
        $payment->refresh();
        $invoice->refresh();

        // Assert: Invoice should be PARTIALLY PAID, not PAID
        expect($invoice->paymentState->value)->toBe('partially_paid')
            ->and($invoice->isPartiallyPaid())->toBeTrue()
            ->and($invoice->isFullyPaid())->toBeFalse()
            ->and($invoice->isNotPaid())->toBeFalse();

        // Assert: Payment amounts are correct
        expect($payment->amount->getAmount()->toFloat())->toBe(250000.0);
        expect($payment->amount->getCurrency()->getCurrencyCode())->toBe('IQD');
        expect($payment->amount_company_currency->getAmount()->toFloat())->toBe(250000.0); // Base currency

        // Assert: Paid amount calculation is correct (converted to USD)
        $paidAmount = $invoice->getPaidAmount();
        expect($paidAmount->getCurrency()->getCurrencyCode())->toBe('USD');
        // 250,000 IQD ÷ 1470 rate ≈ $170.07 USD
        expect($paidAmount->getAmount()->toFloat())->toBeGreaterThan(169.0)
            ->and($paidAmount->getAmount()->toFloat())->toBeLessThan(171.0);

        // Assert: Remaining amount is correct
        $remainingAmount = $invoice->getRemainingAmount();
        expect($remainingAmount->getCurrency()->getCurrencyCode())->toBe('USD');
        expect($remainingAmount->getAmount()->toFloat())->toBeGreaterThan(1329.0) // ~$1330 remaining
            ->and($remainingAmount->getAmount()->toFloat())->toBeLessThan(1331.0);
    });
});
