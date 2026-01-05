<?php

namespace Modules\Accounting\Tests\Feature\Listeners\Consolidation;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Models\VendorBill;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Events\InvoiceConfirmed;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD']);
});

test('it handles invoice confirmed event and creates reciprocal bill', function () {
    // 1. Setup Data
    $parentCompany = Company::factory()->create(['name' => 'Parent Co', 'currency_id' => $this->currency->id]);
    $subsidiaryCompany = Company::factory()->create(['name' => 'Subsidiary Co', 'currency_id' => $this->currency->id]);

    $icPartner = Partner::factory()->for($parentCompany)->create([
        'name' => 'Subsidiary Partner',
        'linked_company_id' => $subsidiaryCompany->id,
    ]);

    $parentAsVendor = Partner::factory()->for($subsidiaryCompany)->create([
        'name' => 'Parent Vendor',
        'linked_company_id' => $parentCompany->id,
    ]);

    // Ensure subsidiary has fallback expense account
    Account::factory()->for($subsidiaryCompany)->create([
        'type' => AccountType::Expense,
        'code' => '600000',
    ]);

    $invoice = Invoice::factory()
        ->for($parentCompany)
        ->for($icPartner, 'customer')
        ->create([
            'status' => InvoiceStatus::Draft,
            'currency_id' => $this->currency->id,
        ]);

    InvoiceLine::factory()->for($invoice)->create([
        'quantity' => 10,
        'unit_price' => 100, // 100.00
        'description' => 'Consulting Services',
    ]);

    $invoice->refresh();

    // 2. Dispatch Event
    InvoiceConfirmed::dispatch($invoice);

    // 3. Assert Listener Effect (Vendor Bill created)
    $this->assertDatabaseHas('vendor_bills', [
        'company_id' => $subsidiaryCompany->id,
        'vendor_id' => $parentAsVendor->id,
        'inter_company_source_id' => $invoice->id,
        'inter_company_source_type' => Invoice::class,
    ]);

    $vendorBill = VendorBill::where('inter_company_source_id', $invoice->id)->first();
    expect((float) $vendorBill->total_amount->getAmount()->toFloat())->toEqual(1000.0);
    expect((float) $vendorBill->lines->first()->quantity)->toEqual(10.0);
});
