<?php

use Modules\Sales\Models\Invoice;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;

describe('NumberingSettingsChangeRule', function () {
    uses(WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->rule = new \Modules\Foundation\Rules\NumberingSettingsChangeRule($this->company);
    });

    it('passes validation when no posted documents exist', function () {
        $failCalled = false;
        $failMessage = '';

        $fail = function ($message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        };

        $this->rule->validate('numbering_settings', [], $fail);

        expect($failCalled)->toBeFalse();
    });

    it('fails validation when posted invoices exist', function () {
        // Create a posted invoice
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
            'invoice_number' => 'INV-00001',
        ]);

        $failCalled = false;
        $failMessage = '';

        $fail = function ($message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        };

        $this->rule->validate('numbering_settings', [], $fail);

        expect($failCalled)->toBeTrue();
        expect($failMessage)->toContain(__('numbering.validation.cannot_change_posted_exist'));
        expect($failMessage)->toContain(__('numbering.validation.posted_invoices_exist'));
    });

    it('fails validation when posted vendor bills exist', function () {
        // Create a posted vendor bill
        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Posted,
            'bill_reference' => 'BILL-00001',
        ]);

        $failCalled = false;
        $failMessage = '';

        $fail = function ($message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        };

        $this->rule->validate('numbering_settings', [], $fail);

        expect($failCalled)->toBeTrue();
        expect($failMessage)->toContain(__('numbering.validation.cannot_change_posted_exist'));
        expect($failMessage)->toContain(__('numbering.validation.posted_bills_exist'));
    });

    it('fails validation when both posted invoices and bills exist', function () {
        // Create posted documents
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
            'invoice_number' => 'INV-00001',
        ]);

        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Posted,
            'bill_reference' => 'BILL-00001',
        ]);

        $failCalled = false;
        $failMessage = '';

        $fail = function ($message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        };

        $this->rule->validate('numbering_settings', [], $fail);

        expect($failCalled)->toBeTrue();
        expect($failMessage)->toContain(__('numbering.validation.cannot_change_posted_exist'));
        expect($failMessage)->toContain(__('numbering.validation.posted_invoices_exist'));
        expect($failMessage)->toContain(__('numbering.validation.posted_bills_exist'));
    });

    it('passes validation when only draft documents exist', function () {
        // Create draft documents (should not prevent changes)
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
            'invoice_number' => null,
        ]);

        VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            // Draft bills can have bill_reference (it's just not auto-generated)
        ]);

        $failCalled = false;
        $failMessage = '';

        $fail = function ($message) use (&$failCalled, &$failMessage) {
            $failCalled = true;
            $failMessage = $message;
        };

        $this->rule->validate('numbering_settings', [], $fail);

        expect($failCalled)->toBeFalse();
    });
});
