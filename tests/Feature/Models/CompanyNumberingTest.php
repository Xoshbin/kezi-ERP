<?php

use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

describe('Company Numbering Settings', function () {
    uses(WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
    });

    it('provides default numbering settings', function () {
        $defaults = $this->company->getDefaultNumberingSettings();

        expect($defaults)->toBeArray();
        expect($defaults)->toHaveKey('invoice');
        expect($defaults)->toHaveKey('vendor_bill');

        expect($defaults['invoice']['type'])->toBe(\Modules\Foundation\Enums\Settings\NumberingType::SLASH_YEAR_MONTH->value);
        expect($defaults['invoice']['prefix'])->toBe('INV');
        expect($defaults['invoice']['padding'])->toBe(7);

        expect($defaults['vendor_bill']['type'])->toBe(\Modules\Foundation\Enums\Settings\NumberingType::SLASH_YEAR_MONTH->value);
        expect($defaults['vendor_bill']['prefix'])->toBe('BILL');
        expect($defaults['vendor_bill']['padding'])->toBe(7);
    });

    it('returns default settings when numbering_settings is null', function () {
        $this->company->numbering_settings = null;
        $this->company->save();

        $settings = $this->company->getNumberingSettings();
        $defaults = $this->company->getDefaultNumberingSettings();

        expect($settings)->toEqual($defaults);
    });

    it('returns custom settings when set', function () {
        $customSettings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_PREFIX->value,
                'prefix' => 'INVOICE',
                'padding' => 6,
            ],
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SLASH_SEPARATED->value,
                'prefix' => 'PURCHASE',
                'padding' => 4,
            ],
        ];

        $this->company->numbering_settings = $customSettings;
        $this->company->save();

        $settings = $this->company->getNumberingSettings();

        expect($settings)->toEqual($customSettings);
    });

    it('gets invoice numbering config', function () {
        $config = $this->company->getInvoiceNumberingConfig();

        expect($config)->toBeArray();
        expect($config)->toHaveKey('type');
        expect($config)->toHaveKey('prefix');
        expect($config)->toHaveKey('padding');
    });

    it('gets vendor bill numbering config', function () {
        $config = $this->company->getVendorBillNumberingConfig();

        expect($config)->toBeArray();
        expect($config)->toHaveKey('type');
        expect($config)->toHaveKey('prefix');
        expect($config)->toHaveKey('padding');
    });

    it('allows numbering changes when no posted documents exist', function () {
        expect($this->company->canChangeNumberingSettings())->toBeTrue();
        expect($this->company->getNumberingChangeValidationErrors())->toBeEmpty();
    });

    it('prevents numbering changes when posted invoices exist', function () {
        // Create a posted invoice
        $invoice = \Modules\Sales\Models\Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
            'invoice_number' => 'INV-00001',
        ]);

        expect($this->company->canChangeNumberingSettings())->toBeFalse();

        $errors = $this->company->getNumberingChangeValidationErrors();
        expect($errors)->not->toBeEmpty();
        expect($errors)->toContain(__('foundation::numbering.validation.posted_invoices_exist'));
    });

    it('prevents numbering changes when posted vendor bills exist', function () {
        // Create a posted vendor bill
        $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Posted,
            'bill_reference' => 'BILL-00001',
        ]);

        expect($this->company->canChangeNumberingSettings())->toBeFalse();

        $errors = $this->company->getNumberingChangeValidationErrors();
        expect($errors)->not->toBeEmpty();
        expect($errors)->toContain(__('foundation::numbering.validation.posted_bills_exist'));
    });

    it('prevents numbering changes when both posted invoices and bills exist', function () {
        // Create posted documents
        \Modules\Sales\Models\Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Posted,
            'invoice_number' => 'INV-00001',
        ]);

        \Modules\Purchase\Models\VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Posted,
            'bill_reference' => 'BILL-00001',
        ]);

        expect($this->company->canChangeNumberingSettings())->toBeFalse();

        $errors = $this->company->getNumberingChangeValidationErrors();
        expect($errors)->toHaveCount(2);
        expect($errors)->toContain(__('foundation::numbering.validation.posted_invoices_exist'));
        expect($errors)->toContain(__('foundation::numbering.validation.posted_bills_exist'));
    });

    it('allows numbering changes when only draft documents exist', function () {
        // Create draft documents (should not prevent changes)
        \Modules\Sales\Models\Invoice::factory()->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
            'invoice_number' => null,
        ]);

        \Modules\Purchase\Models\VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'status' => VendorBillStatus::Draft,
            // Draft bills can have bill_reference (it's just not auto-generated)
        ]);

        expect($this->company->canChangeNumberingSettings())->toBeTrue();
        expect($this->company->getNumberingChangeValidationErrors())->toBeEmpty();
    });
});
