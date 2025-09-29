<?php

use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Purchase\Services\VendorBillService;
use Modules\Foundation\Enums\Settings\NumberingType;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe('VendorBill Numbering Integration', function () {
    uses(RefreshDatabase::class, WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->vendorBillService = app(VendorBillService::class);
        // User is already created and attached to company in setupWithConfiguredCompany()
    });

    it('auto-generates bill numbers when posting vendor bills', function () {
        // Create a draft vendor bill without bill_reference
        $vendorBill = VendorBill::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            'bill_reference' => '', // Empty reference
        ]);

        expect($vendorBill->bill_reference)->toBeEmpty();

        // Post the vendor bill
        $this->vendorBillService->post($vendorBill, $this->user);

        // Refresh the model
        $vendorBill->refresh();

        expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
        expect($vendorBill->bill_reference)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('does not overwrite existing bill references', function () {
        // Create a vendor bill with existing reference
        $vendorBill = VendorBill::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            'bill_reference' => 'MANUAL-001',
        ]);

        // Post the vendor bill
        $this->vendorBillService->post($vendorBill, $this->user);

        // Refresh the model
        $vendorBill->refresh();

        expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
        expect($vendorBill->bill_reference)->toBe('MANUAL-001'); // Should not change
    });

    it('uses custom numbering settings when generating bill numbers', function () {
        // Set custom numbering settings
        $this->company->numbering_settings = [
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_PREFIX->value,
                'prefix' => 'PURCHASE',
                'padding' => 6,
            ],
        ];
        $this->company->save();

        // Create a draft vendor bill
        $vendorBill = VendorBill::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            'bill_reference' => '',
            'bill_date' => '2025-06-15',
        ]);

        // Post the vendor bill
        $this->vendorBillService->post($vendorBill, $this->user);

        // Refresh the model
        $vendorBill->refresh();

        expect($vendorBill->bill_reference)->toBe('2025-PURCHASE-000001');
    });

    it('generates sequential bill numbers', function () {
        // Create and post multiple vendor bills
        $bills = [];

        for ($i = 0; $i < 3; $i++) {
            $bill = VendorBill::factory()->withLines(1)->create([
                'company_id' => $this->company->id,
                'status' => VendorBillStatus::Draft,
                'bill_reference' => '',
            ]);

            $this->vendorBillService->post($bill, $this->user);
            $bill->refresh();
            $bills[] = $bill;
        }

        expect($bills[0]->bill_reference)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
        expect($bills[1]->bill_reference)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
        expect($bills[2]->bill_reference)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('uses bill date for date-based numbering formats', function () {
        // Set year-month numbering
        $this->company->numbering_settings = [
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_MONTH->value,
                'prefix' => 'BILL',
                'padding' => 3,
            ],
        ];
        $this->company->save();

        // Create vendor bill with specific date
        $vendorBill = VendorBill::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            'bill_reference' => '',
            'bill_date' => '2025-03-10',
        ]);

        // Post the vendor bill
        $this->vendorBillService->post($vendorBill, $this->user);

        // Refresh the model
        $vendorBill->refresh();

        expect($vendorBill->bill_reference)->toBe('202503-BILL-001');
    });
});
