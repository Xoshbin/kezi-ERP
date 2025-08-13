<?php

namespace Tests\Feature\Filament\Pages\Reports;

use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Pages\Reports\ViewAgedPayables;
use App\Models\Partner;
use App\Models\VendorBill;
use App\Support\NumberFormatter;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

test('it can render the aged payables page', function () {
    Livewire::test(ViewAgedPayables::class)
        ->assertSuccessful()
        ->assertSee(__('reports.aged_payables_report'));
});

test('it can generate aged payables report', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create(['name' => 'Test Vendor']);
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a past due vendor bill
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 23 days past due
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Action & Assert
    $expectedAmount = NumberFormatter::formatMoneyTo(Money::of(1000, $currency));
    Livewire::test(ViewAgedPayables::class)
        ->fillForm([
            'asOfDate' => $asOfDate->toDateString(),
        ])
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee('Test Vendor')
        ->assertSee($expectedAmount) // Should show in 1-30 days bucket
        ->assertSee(__('reports.aged_payables_report'));
});

test('it shows no data message when no outstanding payables exist', function () {
    // Arrange
    $asOfDate = Carbon::parse('2025-08-12');

    // Action & Assert
    Livewire::test(ViewAgedPayables::class)
        ->fillForm([
            'asOfDate' => $asOfDate->toDateString(),
        ])
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee(__('reports.no_outstanding_payables'));
});

test('it validates required as of date', function () {
    Livewire::test(ViewAgedPayables::class)
        ->fillForm([
            'asOfDate' => '',
        ])
        ->call('generateReport')
        ->assertHasFormErrors(['asOfDate' => 'required']);
});

test('it validates date format', function () {
    Livewire::test(ViewAgedPayables::class)
        ->fillForm([
            'asOfDate' => 'invalid-date',
        ])
        ->call('generateReport')
        ->assertHasFormErrors(['asOfDate' => 'date']);
});

test('it displays correct aging buckets', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create(['name' => 'Test Vendor']);
    $asOfDate = Carbon::parse('2025-08-12');

    // Create vendor bills in different aging buckets
    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-09-01', // Current (not due)
        'bill_date' => '2025-08-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 1-30 days past due
        'bill_date' => '2025-07-01',
        'total_amount' => Money::of(2000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-06-20', // 31-60 days past due
        'bill_date' => '2025-06-01',
        'total_amount' => Money::of(3000, $currency),
        'status' => VendorBillStatus::Posted,
    ]);

    // Action & Assert
    $amount1 = NumberFormatter::formatMoneyTo(Money::of(1000, $currency));
    $amount2 = NumberFormatter::formatMoneyTo(Money::of(2000, $currency));
    $amount3 = NumberFormatter::formatMoneyTo(Money::of(3000, $currency));
    $total = NumberFormatter::formatMoneyTo(Money::of(6000, $currency));

    Livewire::test(ViewAgedPayables::class)
        ->fillForm([
            'asOfDate' => $asOfDate->toDateString(),
        ])
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee('Test Vendor')
        ->assertSee($amount1) // Current bucket
        ->assertSee($amount2) // 1-30 days bucket
        ->assertSee($amount3) // 31-60 days bucket
        ->assertSee($total); // Total
});
