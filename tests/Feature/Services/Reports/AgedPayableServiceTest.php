<?php

namespace Tests\Feature\Services\Reports;

use App\Enums\Purchases\VendorBillStatus;
use App\Enums\Payments\PaymentStatus;
use App\Models\VendorBill;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentDocumentLink;
use App\Services\Reports\AgedPayableService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it generates the aged payable report with correct bucketing', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create vendor bills with different due dates relative to the "as of" date
    // Use Money objects to ensure correct conversion to minor units
    $vendorBill1 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-09-01',
        'bill_date' => '2025-08-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    $vendorBill2 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(2000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    $vendorBill3 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-06-20',
        'bill_date' => '2025-06-01',
        'total_amount' => Money::of(3000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    $vendorBill4 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-05-20',
        'bill_date' => '2025-05-01',
        'total_amount' => Money::of(4000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    $vendorBill5 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-04-20',
        'bill_date' => '2025-04-01',
        'total_amount' => Money::of(5000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Ignored: Fully paid bill
    $paidBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-01',
        'bill_date' => '2025-06-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(1000, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $paidBill->id,
        'amount_applied' => Money::of(1000, $currency),
    ]);

    // Ignored: Bill dated after the "as of" date
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'bill_date' => '2025-09-01',
        'due_date' => '2025-09-15',
        'total_amount' => Money::of(9999, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Action
    $service = app(AgedPayableService::class);
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

    // Assert total for the partner and grand total
    $expectedTotal = Money::of(15000, $currency);
    expect($partnerLine->totalDue)->toEqual($expectedTotal);
    expect($report->grandTotalDue)->toEqual($expectedTotal);
});

test('it handles partially paid vendor bills correctly', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a vendor bill that's partially paid
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 23 days past due
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Create partial payment
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(300, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(300, $currency),
    ]);

    // Action
    $service = app(AgedPayableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(1);

    $partnerLine = $report->reportLines->first();
    expect($partnerLine->bucket1_30)->toEqual(Money::of(700, $currency)); // 1000 - 300
    expect($partnerLine->totalDue)->toEqual(Money::of(700, $currency));
});

test('it excludes draft vendor bills', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a draft vendor bill
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Draft,
    ]);

    // Action
    $service = app(AgedPayableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->grandTotalDue)->toEqual(Money::zero($currency));
});

test('it excludes fully paid vendor bills', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a vendor bill
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Create full payment
    $payment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(1000, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $payment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(1000, $currency),
    ]);

    // Action
    $service = app(AgedPayableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->grandTotalDue)->toEqual(Money::zero($currency));
});

test('it handles multiple partners correctly', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partnerA = Partner::factory()->for($this->company)->create(['name' => 'Partner A']);
    $partnerB = Partner::factory()->for($this->company)->create(['name' => 'Partner B']);
    $asOfDate = Carbon::parse('2025-08-12');

    // Create vendor bills for different partners
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partnerA->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-09-01', // Current
        'bill_date' => '2025-08-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partnerB->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 1-30 days
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(2000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Action
    $service = app(AgedPayableService::class);
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

test('it excludes vendor bills dated after as of date', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a vendor bill dated after the "as of" date
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'bill_date' => '2025-08-15', // After as of date
        'due_date' => '2025-09-15',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Action
    $service = app(AgedPayableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(0);
    expect($report->grandTotalDue)->toEqual(Money::zero($currency));
});

test('it only includes confirmed and reconciled payments', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create();
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a vendor bill
    $vendorBill = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20',
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Create draft payment (should be ignored)
    $draftPayment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(300, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Draft,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $draftPayment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(300, $currency),
    ]);

    // Create confirmed payment (should be included)
    $confirmedPayment = Payment::factory()->for($this->company)->create([
        'amount' => Money::of(200, $currency),
        'currency_id' => $this->company->currency_id,
        'status' => PaymentStatus::Confirmed,
    ]);

    PaymentDocumentLink::create([
        'payment_id' => $confirmedPayment->id,
        'vendor_bill_id' => $vendorBill->id,
        'amount_applied' => Money::of(200, $currency),
    ]);

    // Action
    $service = app(AgedPayableService::class);
    $report = $service->generate($this->company, $asOfDate);

    // Assert
    expect($report->reportLines)->toHaveCount(1);

    $partnerLine = $report->reportLines->first();
    expect($partnerLine->bucket1_30)->toEqual(Money::of(800, $currency)); // 1000 - 200 (draft payment ignored)
    expect($partnerLine->totalDue)->toEqual(Money::of(800, $currency));
});
