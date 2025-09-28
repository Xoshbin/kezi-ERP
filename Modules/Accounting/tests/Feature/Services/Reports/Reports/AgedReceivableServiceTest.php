<?php

namespace Modules\Accounting\Tests\Feature\Services\Reports;

use Carbon\Carbon;
use Brick\Money\Money;
use Modules\Sales\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Payment\Models\PaymentDocumentLink;
use Modules\Payment\Enums\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Services\Reports\AgedReceivableService;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates the aged receivable report with correct bucketing', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create invoices with different due dates relative to the "as of" date
    // Use Money objects to ensure correct conversion to minor units
    $invoice1 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-09-01',
        'invoice_date' => '2025-08-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // 2. 1-30 days past due
    $invoice2 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'invoice_date' => '2025-07-01',
        'total_amount' => Money::of(2000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // 3. 31-60 days past due
    $invoice3 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-06-20',
        'invoice_date' => '2025-06-01',
        'total_amount' => Money::of(3000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // 4. 61-90 days past due
    $invoice4 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-05-20',
        'invoice_date' => '2025-05-01',
        'total_amount' => Money::of(4000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // 5. 91+ days past due
    $invoice5 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-04-20',
        'invoice_date' => '2025-04-01',
        'total_amount' => Money::of(5000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // 6. Ignored: Fully paid invoice
    $invoice6 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-01',
        'invoice_date' => '2025-06-15',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // Create payment that fully pays invoice6
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(1000, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice6->id,
        'amount_applied' => Money::of(1000, $currency),
    ]);

    // 7. Ignored: Invoice issued after the "as of" date
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'invoice_date' => '2025-09-01',
        'due_date' => '2025-09-15',
        'total_amount' => 9999000, // 9999 in major units
        'status' => InvoiceStatus::Posted,
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(1);

    $partnerLine = $report->reportLines->first();
    expect($partnerLine->partnerId)->toBe($partner->id);

    // Assert each bucket has the correct amount
    expect($partnerLine->current)->toEqual(Money::of(1000, $currency));
    expect($partnerLine->bucket1_30)->toEqual(Money::of(2000, $currency));
    expect($partnerLine->bucket31_60)->toEqual(Money::of(3000, $currency));
    expect($partnerLine->bucket61_90)->toEqual(Money::of(4000, $currency));
    expect($partnerLine->bucket90_plus)->toEqual(Money::of(5000, $currency));

    // Assert total for the partner
    $expectedTotal = Money::of(15000, $currency); // Sum of all unpaid amounts
    expect($partnerLine->totalDue)->toEqual($expectedTotal);

    // Assert grand totals
    expect($report->grandTotalDue)->toEqual($expectedTotal);
    expect($report->totalCurrent)->toEqual(Money::of(1000, $currency));
    expect($report->totalBucket1_30)->toEqual(Money::of(2000, $currency));
    expect($report->totalBucket31_60)->toEqual(Money::of(3000, $currency));
    expect($report->totalBucket61_90)->toEqual(Money::of(4000, $currency));
    expect($report->totalBucket90_plus)->toEqual(Money::of(5000, $currency));
});

test('it handles partially paid invoices correctly', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create an invoice that's partially paid
    $invoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 23 days past due
        'invoice_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // Create partial payment
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(300, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'invoice_id' => $invoice->id,
        'amount_applied' => Money::of(300, $currency),
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(1);

    $partnerLine = $report->reportLines->first();
    expect($partnerLine->bucket1_30)->toEqual(Money::of(700, $currency)); // 1000 - 300
    expect($partnerLine->totalDue)->toEqual(Money::of(700, $currency));
});

test('it excludes draft invoices', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a draft invoice
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'invoice_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Draft,
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->grandTotalDue)->toEqual(Money::zero($currency));
});

test('it handles multiple partners correctly', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner1 = Partner::factory()->for($this->company)->create(['name' => 'Partner A']);
    $partner2 = Partner::factory()->for($this->company)->create(['name' => 'Partner B']);
    $asOfDate = Carbon::parse('2025-08-12');

    // Partner 1 - Current invoice
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner1->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-09-01',
        'invoice_date' => '2025-08-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // Partner 2 - Past due invoice
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner2->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'invoice_date' => '2025-07-01',
        'total_amount' => Money::of(2000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // Action
    $service = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(2);
    expect($report->grandTotalDue)->toEqual(Money::of(3000, $currency));
    expect($report->totalCurrent)->toEqual(Money::of(1000, $currency));
    expect($report->totalBucket1_30)->toEqual(Money::of(2000, $currency));

    // Check individual partners
    $partnerALine = $report->reportLines->firstWhere('partnerName', 'Partner A');
    $partnerBLine = $report->reportLines->firstWhere('partnerName', 'Partner B');

    expect($partnerALine->current)->toEqual(Money::of(1000, $currency));
    expect($partnerBLine->bucket1_30)->toEqual(Money::of(2000, $currency));
});
