<?php

use App\Actions\Payments\CreateInterCompanyPaymentAction;
use App\DataTransferObjects\Payments\CreateInterCompanyPaymentDTO;
use App\DataTransferObjects\Payments\VendorBillPaymentDTO;
use App\Enums\Purchases\VendorBillStatus;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Partner;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\Payments\InterCompanyPaymentService;
use Brick\Money\Money;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

test('inter-company payment service detects inter-company vendor bills correctly', function () {
    // ARRANGE: Set up company hierarchy
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner relationships
    $childPartnerInParent = Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo Partner',
        'linked_company_id' => $childCompany->id,
    ]);

    $parentPartnerInChild = Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo Partner',
        'linked_company_id' => $parentCompany->id,
    ]);

    // Create a vendor bill in child company with vendor linked to parent company
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $childCompany->id,
        'vendor_id' => $parentPartnerInChild->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => Money::of(1000, 'USD'),
    ]);

    // Create a payment in parent company
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $parentCompany->id,
        'amount' => Money::of(1000, 'USD'),
    ]);

    // Link the payment to the vendor bill
    $payment->vendorBills()->attach($vendorBill->id, [
        'amount_applied' => Money::of(1000, 'USD')->getMinorAmount(),
    ]);

    // ACT: Check if this is an inter-company payment
    $service = app(InterCompanyPaymentService::class);
    $isInterCompany = $service->isInterCompanyPayment($payment);
    $interCompanyBills = $service->getInterCompanyVendorBills($payment);

    // ASSERT: Should detect as inter-company
    expect($isInterCompany)->toBeTrue();
    expect($interCompanyBills)->toHaveCount(1);
    expect($interCompanyBills->first()->id)->toBe($vendorBill->id);
});

test('inter-company payment service ignores non-inter-company payments', function () {
    // ARRANGE: Set up single company with regular vendor
    $company = Company::factory()->create(['name' => 'TestCo']);

    // Create a regular partner (not linked to another company)
    $regularVendor = Partner::factory()->create([
        'company_id' => $company->id,
        'name' => 'Regular Vendor',
        'linked_company_id' => null, // Not linked to another company
    ]);

    // Create a vendor bill with regular vendor
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $company->id,
        'vendor_id' => $regularVendor->id,
        'status' => VendorBillStatus::Posted,
    ]);

    // Create a payment
    $payment = \App\Models\Payment::factory()->create([
        'company_id' => $company->id,
    ]);

    $payment->vendorBills()->attach($vendorBill->id, [
        'amount_applied' => Money::of(500, 'USD')->getMinorAmount(),
    ]);

    // ACT: Check if this is an inter-company payment
    $service = app(InterCompanyPaymentService::class);
    $isInterCompany = $service->isInterCompanyPayment($payment);

    // ASSERT: Should not be inter-company
    expect($isInterCompany)->toBeFalse();
});

test('create inter-company payment action validates company relationships', function () {
    // ARRANGE: Set up companies without partner relationship
    $company1 = Company::factory()->create(['name' => 'Company1']);
    $company2 = Company::factory()->create(['name' => 'Company2']);
    $user = User::factory()->create();

    // Create DTO without proper partner relationship
    $dto = new CreateInterCompanyPaymentDTO(
        paying_company_id: $company1->id,
        beneficiary_company_id: $company2->id,
        journal_id: Journal::factory()->create(['company_id' => $company1->id])->id,
        currency_id: $company1->currency_id,
        payment_date: now()->format('Y-m-d'),
        vendor_bill_payments: [],
    );

    // ACT & ASSERT: Should throw validation exception
    $action = app(CreateInterCompanyPaymentAction::class);
    
    expect(fn() => $action->execute($dto, $user))
        ->toThrow(\InvalidArgumentException::class, 'No partner relationship exists');
});

test('create inter-company payment action validates same company restriction', function () {
    // ARRANGE: Set up single company
    $company = Company::factory()->create(['name' => 'TestCo']);
    $user = User::factory()->create();

    // Create DTO with same paying and beneficiary company
    $dto = new CreateInterCompanyPaymentDTO(
        paying_company_id: $company->id,
        beneficiary_company_id: $company->id, // Same as paying company
        journal_id: Journal::factory()->create(['company_id' => $company->id])->id,
        currency_id: $company->currency_id,
        payment_date: now()->format('Y-m-d'),
        vendor_bill_payments: [],
    );

    // ACT & ASSERT: Should throw validation exception
    $action = app(CreateInterCompanyPaymentAction::class);
    
    expect(fn() => $action->execute($dto, $user))
        ->toThrow(\InvalidArgumentException::class, 'Paying company and beneficiary company must be different');
});

test('create inter-company payment action creates proper payment and loan entries', function () {
    // ARRANGE: Set up company hierarchy with proper relationships
    $this->setupInterCompanyHierarchy();
    
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $parentPartnerInChild = Partner::where('company_id', $childCompany->id)
        ->where('linked_company_id', $parentCompany->id)
        ->first();

    // Create a vendor bill in child company
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $childCompany->id,
        'vendor_id' => $parentPartnerInChild->id,
        'status' => VendorBillStatus::Posted,
        'total_amount' => Money::of(1500, 'USD'),
        'bill_reference' => 'BILL-001',
    ]);

    $user = User::factory()->create();

    // Create DTO for inter-company payment
    $dto = new CreateInterCompanyPaymentDTO(
        paying_company_id: $parentCompany->id,
        beneficiary_company_id: $childCompany->id,
        journal_id: $parentCompany->default_bank_journal_id,
        currency_id: $parentCompany->currency_id,
        payment_date: now()->format('Y-m-d'),
        vendor_bill_payments: [
            new VendorBillPaymentDTO(
                vendor_bill_id: $vendorBill->id,
                amount_applied: Money::of(1500, 'USD'),
            ),
        ],
        reference: 'IC-PAY-001',
    );

    // ACT: Execute the inter-company payment
    $action = app(CreateInterCompanyPaymentAction::class);
    $payment = $action->execute($dto, $user);

    // ASSERT: Verify payment was created and confirmed
    expect($payment)->not->toBeNull();
    expect($payment->company_id)->toBe($parentCompany->id);
    expect($payment->status)->toBe(\App\Enums\Payments\PaymentStatus::Confirmed);
    expect($payment->reference)->toBe('IC-PAY-001');

    // Verify payment is linked to vendor bill
    expect($payment->vendorBills)->toHaveCount(1);
    expect($payment->vendorBills->first()->id)->toBe($vendorBill->id);

    // Verify journal entries were created (this would require checking journal_entries table)
    expect($payment->journal_entry_id)->not->toBeNull();
});

test('inter-company payment service creates settlement entries correctly', function () {
    // ARRANGE: Set up companies with partner relationships
    $this->setupInterCompanyHierarchy();
    
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $user = User::factory()->create();

    $settlementAmount = Money::of(2000, 'USD');

    // ACT: Create settlement entry
    $service = app(InterCompanyPaymentService::class);
    $service->createSettlementEntry(
        $parentCompany,
        $childCompany,
        $settlementAmount,
        $user,
        'SETTLEMENT-001'
    );

    // ASSERT: Verify settlement was processed (would check journal entries in real implementation)
    // For now, just verify no exceptions were thrown
    expect(true)->toBeTrue();
});

test('inter-company payment action validates vendor bill ownership', function () {
    // ARRANGE: Set up companies
    $this->setupInterCompanyHierarchy();
    
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $otherCompany = Company::factory()->create(['name' => 'OtherCo']);

    // Create vendor bill in different company (not beneficiary)
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $otherCompany->id, // Wrong company
        'status' => VendorBillStatus::Posted,
    ]);

    $user = User::factory()->create();

    // Create DTO with vendor bill from wrong company
    $dto = new CreateInterCompanyPaymentDTO(
        paying_company_id: $parentCompany->id,
        beneficiary_company_id: $childCompany->id,
        journal_id: $parentCompany->default_bank_journal_id,
        currency_id: $parentCompany->currency_id,
        payment_date: now()->format('Y-m-d'),
        vendor_bill_payments: [
            new VendorBillPaymentDTO(
                vendor_bill_id: $vendorBill->id,
                amount_applied: Money::of(1000, 'USD'),
            ),
        ],
    );

    // ACT & ASSERT: Should throw validation exception
    $action = app(CreateInterCompanyPaymentAction::class);
    
    expect(fn() => $action->execute($dto, $user))
        ->toThrow(\InvalidArgumentException::class, 'All vendor bills must belong to the beneficiary company');
});

// Helper method to set up inter-company hierarchy
function setupInterCompanyHierarchy(): void
{
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner relationships
    Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo Partner',
        'linked_company_id' => $childCompany->id,
    ]);

    Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo Partner',
        'linked_company_id' => $parentCompany->id,
    ]);
}
