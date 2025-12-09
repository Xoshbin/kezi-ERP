<?php

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->currency = $this->company->currency;
});

test('customer partner calculates outstanding balance correctly', function () {
    $customer = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Create posted invoices
    $invoice1 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(1000, $this->currency->code),
        'status' => InvoiceStatus::Posted,
    ]);

    $invoice2 = Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(500, $this->currency->code),
        'status' => InvoiceStatus::Posted,
    ]);

    // Create draft invoice (should not be included)
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(200, $this->currency->code),
        'status' => InvoiceStatus::Draft,
    ]);

    $outstandingBalance = $customer->getCustomerOutstandingBalance();

    expect($outstandingBalance->getAmount()->toFloat())->toBe(1500.0);
});

test('vendor partner calculates outstanding balance correctly', function () {
    $vendor = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    // Create posted vendor bills
    $bill1 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(800, $this->currency->code),
        'status' => VendorBillStatus::Posted,
    ]);

    $bill2 = VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $vendor->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(300, $this->currency->code),
        'status' => VendorBillStatus::Posted,
    ]);

    $outstandingBalance = $vendor->getVendorOutstandingBalance();

    expect($outstandingBalance->getAmount()->toFloat())->toBe(1100.0);
});

test('partner calculates overdue balances correctly', function () {
    $customer = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Create overdue invoice
    $overdueInvoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(600, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'due_date' => Carbon::yesterday(),
    ]);

    // Create current invoice (not overdue)
    $currentInvoice = Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(400, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'due_date' => Carbon::tomorrow(),
    ]);

    $overdueBalance = $customer->getCustomerOverdueBalance();
    $totalBalance = $customer->getCustomerOutstandingBalance();

    expect($overdueBalance->getAmount()->toFloat())->toBe(600.0);
    expect($totalBalance->getAmount()->toFloat())->toBe(1000.0);
});

test('both type partner calculates both customer and vendor balances', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Both,
    ]);

    // Create invoice (as customer)
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(500, $this->currency->code),
        'status' => InvoiceStatus::Posted,
    ]);

    // Create vendor bill (as vendor)
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(300, $this->currency->code),
        'status' => VendorBillStatus::Posted,
    ]);

    $customerBalance = $partner->getCustomerOutstandingBalance();
    $vendorBalance = $partner->getVendorOutstandingBalance();

    expect($customerBalance->getAmount()->toFloat())->toBe(500.0);
    expect($vendorBalance->getAmount()->toFloat())->toBe(300.0);
});

test('partner returns zero balance for wrong type', function () {
    $customer = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    $vendor = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);

    // Customer should have zero vendor balance
    $customerVendorBalance = $customer->getVendorOutstandingBalance();
    expect($customerVendorBalance->isZero())->toBeTrue();

    // Vendor should have zero customer balance
    $vendorCustomerBalance = $vendor->getCustomerOutstandingBalance();
    expect($vendorCustomerBalance->isZero())->toBeTrue();
});

test('partner calculates last transaction date correctly', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Both,
    ]);

    $recentDate = Carbon::today();
    $olderDate = Carbon::yesterday();

    // Create invoice with recent date
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'invoice_date' => $recentDate,
        'status' => InvoiceStatus::Posted,
    ]);

    // Create vendor bill with older date
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'bill_date' => $olderDate,
        'status' => VendorBillStatus::Posted,
    ]);

    $lastTransactionDate = $partner->getLastTransactionDate();

    expect($lastTransactionDate->isSameDay($recentDate))->toBeTrue();
});

test('partner with no transactions returns null for last transaction date', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    $lastTransactionDate = $partner->getLastTransactionDate();

    expect($lastTransactionDate)->toBeNull();
});

test('partner detects overdue amounts correctly', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Initially no overdue amounts
    expect($partner->hasOverdueAmounts())->toBeFalse();

    // Create overdue invoice
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(100, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'due_date' => Carbon::yesterday(),
    ]);

    // Refresh the partner to clear any cached relationships
    $partner->refresh();

    expect($partner->hasOverdueAmounts())->toBeTrue();
});

test('partner calculates due within days correctly', function () {
    $customer = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Create invoice due in 5 days
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(500, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'due_date' => Carbon::today()->addDays(5),
        'invoice_date' => Carbon::today(),
    ]);

    // Create invoice due in 15 days
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $customer->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(300, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'due_date' => Carbon::today()->addDays(15),
        'invoice_date' => Carbon::today(),
    ]);

    $dueIn7Days = $customer->getCustomerDueWithinDays(7);
    $dueIn30Days = $customer->getCustomerDueWithinDays(30);

    expect($dueIn7Days->getAmount()->toFloat())->toBe(500.0);
    expect($dueIn30Days->getAmount()->toFloat())->toBe(800.0);
});

test('partner calculates monthly transaction value correctly', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Both,
    ]);

    // Create invoice this month
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(400, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'invoice_date' => Carbon::now(),
    ]);

    // Create vendor bill this month
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(200, $this->currency->code),
        'status' => VendorBillStatus::Posted,
        'bill_date' => Carbon::now(),
    ]);

    // Create invoice last month (should not be included)
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->currency->id,
        'total_amount' => Money::of(100, $this->currency->code),
        'status' => InvoiceStatus::Posted,
        'invoice_date' => Carbon::now()->subMonth(),
    ]);

    $monthlyValue = $partner->getMonthlyTransactionValue();

    expect($monthlyValue->getAmount()->toFloat())->toBe(600.0);
});
