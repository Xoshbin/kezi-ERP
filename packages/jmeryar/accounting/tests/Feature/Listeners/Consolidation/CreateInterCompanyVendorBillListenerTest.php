<?php

namespace Jmeryar\Accounting\Tests\Feature\Listeners\Consolidation;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Events\InvoiceConfirmed;
use Jmeryar\Sales\Models\Invoice;
use Jmeryar\Sales\Models\InvoiceLine;

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
