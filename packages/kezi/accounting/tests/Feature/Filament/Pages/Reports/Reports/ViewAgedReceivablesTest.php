<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Pages\Reports;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports\ViewAgedReceivables;
use Kezi\Foundation\Models\Partner;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

test('it can render the aged receivables page', function () {
    Livewire::test(ViewAgedReceivables::class)
        ->assertSuccessful()
        ->assertSee(__('accounting::reports.aged_receivables_report'))
        ->assertSee(__('accounting::reports.report_parameters'))
        ->assertSee(__('accounting::reports.as_of_date'))
        ->assertSee(__('accounting::reports.generate_report'));
});

test('it can generate aged receivables report', function () {
    // Arrange
    $currency = $this->company->currency->code;
    $partner = Partner::factory()->for($this->company)->create(['name' => 'Test Partner']);
    $asOfDate = Carbon::parse('2025-08-12');

    // Create a past due invoice
    Invoice::factory()->for($this->company)->create([
        'customer_id' => $partner->id,
        'currency_id' => $this->company->currency_id,
        'due_date' => '2025-07-20', // 23 days past due
        'invoice_date' => '2025-07-01',
        'total_amount' => Money::of(1000, $currency),
        'status' => InvoiceStatus::Posted,
    ]);

    // Action & Assert
    $component = Livewire::test(ViewAgedReceivables::class)
        ->set('asOfDate', $asOfDate->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee('Test Partner')
        ->assertSee(__('accounting::reports.total'));

    // Check that the report data contains the expected values
    expect($component->get('reportData'))->not->toBeNull();
    expect($component->get('reportData')['reportLines'])->toHaveCount(1);
    expect($component->get('reportData')['reportLines'][0]['partnerName'])->toBe('Test Partner');
});

test('it validates required as of date', function () {
    Livewire::test(ViewAgedReceivables::class)
        ->set('asOfDate', '')
        ->call('generateReport')
        ->assertHasErrors(['asOfDate' => 'required']);
});

test('it shows no data message when no receivables exist', function () {
    Livewire::test(ViewAgedReceivables::class)
        ->set('asOfDate', Carbon::now()->toDateString())
        ->call('generateReport')
        ->assertSuccessful()
        ->assertSee(__('accounting::reports.no_outstanding_receivables'));
});
