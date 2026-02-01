<?php

namespace Kezi\Accounting\Tests\Feature\Services\Consolidation;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Kezi\Accounting\Services\Consolidation\InterCompanyDocumentService;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup currencies
    $this->currency = Currency::factory()->createSafely(['code' => 'USD']);
});

test('it creates reciprocal vendor bill when invoice is posted to inter-company partner', function () {
    // 1. Setup Companies
    $parentCompany = Company::factory()->create(['name' => 'Parent Co', 'currency_id' => $this->currency->id]);
    $subsidiaryCompany = Company::factory()->create(['name' => 'Subsidiary Co', 'currency_id' => $this->currency->id]);

    // 2. Setup Inter-Company Partner in Parent (represents Subsidiary)
    $icPartner = Partner::factory()->for($parentCompany)->create([
        'name' => 'Subsidiary Partner',
        'linked_company_id' => $subsidiaryCompany->id,
    ]);

    // 3. Setup Vendor Partner in Subsidiary (represents Parent)
    // The system needs to know which partner in Subsidiary represents the Parent to set as Vendor.
    // For now, we assume one exists or we create it.
    // Ideally, the service should find a partner in Subsidiary linked to Parent.
    $parentAsVendor = Partner::factory()->for($subsidiaryCompany)->create([
        'name' => 'Parent Vendor',
        'linked_company_id' => $parentCompany->id,
    ]);

    // 3b. Setup Expense Account in Subsidiary (for Vendor Bill Lines)
    $expenseAccount = \Kezi\Accounting\Models\Account::factory()->for($subsidiaryCompany)->create([
        'name' => 'General Expense',
        'code' => '600000',
        'type' => \Kezi\Accounting\Enums\Accounting\AccountType::Expense,
    ]);

    // 4. Create Invoice in Parent
    $invoice = Invoice::factory()
        ->for($parentCompany)
        ->for($icPartner, 'customer')
        ->create([
            'status' => InvoiceStatus::Draft,
            'currency_id' => $this->currency->id,
        ]);

    InvoiceLine::factory()->for($invoice)->create([
        'quantity' => 10,
        'unit_price' => 100,
        'description' => 'Consulting Services',
    ]);

    // 5. Execute Service (Directly first, then event listener later)
    $invoice->refresh(); // Ensure totals are calculated

    /** @var InterCompanyDocumentService $service */
    $service = app(InterCompanyDocumentService::class);
    $vendorBill = $service->createReciprocalVendorBill($invoice);

    // 6. Assertions
    expect($vendorBill)->not->toBeNull();
    expect($vendorBill)->toBeInstanceOf(VendorBill::class);
    expect($vendorBill->company_id)->toBe($subsidiaryCompany->id); // Created in Subsidiary
    expect($vendorBill->vendor_id)->toBe($parentAsVendor->id); // Vendor is Parent
    expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
    expect($vendorBill->inter_company_source_id)->toBe($invoice->id);
    expect($vendorBill->inter_company_source_type)->toBe(Invoice::class);

    // Check totals
    expect($vendorBill->total_amount->getAmount()->toFloat())->toBe($invoice->total_amount->getAmount()->toFloat());

    // Check lines
    expect($vendorBill->lines)->toHaveCount(1);
    expect($vendorBill->lines->first()->description)->toBe('Consulting Services');
    expect((float) $vendorBill->lines->first()->quantity)->toBe(10.0);
});
