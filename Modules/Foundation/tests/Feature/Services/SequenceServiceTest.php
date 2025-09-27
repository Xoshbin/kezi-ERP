<?php

use App\Enums\Settings\NumberingType;
use App\Models\Company;
use App\Services\SequenceService;
use Carbon\Carbon;
use Tests\Traits\WithConfiguredCompany;

describe('SequenceService', function () {
    uses(WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->sequenceService = app(\Modules\Foundation\Services\SequenceService::class);
    });

    it('generates invoice numbers using company settings', function () {
        // Set custom numbering settings
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SIMPLE->value,
                'prefix' => 'INVOICE',
                'padding' => 6,
            ],
        ];
        $this->company->save();

        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company);

        expect($invoiceNumber)->toBe('INVOICE-000001');
    });

    it('generates vendor bill numbers using company settings', function () {
        // Set custom numbering settings
        $this->company->numbering_settings = [
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SIMPLE->value,
                'prefix' => 'PURCHASE',
                'padding' => 4,
            ],
        ];
        $this->company->save();

        $billNumber = $this->sequenceService->getNextVendorBillNumber($this->company);

        expect($billNumber)->toBe('PURCHASE-0001');
    });

    it('generates sequential numbers', function () {
        $first = $this->sequenceService->getNextInvoiceNumber($this->company);
        $second = $this->sequenceService->getNextInvoiceNumber($this->company);
        $third = $this->sequenceService->getNextInvoiceNumber($this->company);

        expect($first)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($second)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($third)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('generates numbers with year prefix format', function () {
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_PREFIX->value,
                'prefix' => 'INV',
                'padding' => 5,
            ],
        ];
        $this->company->save();

        $date = Carbon::create(2025, 6, 15);
        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company, $date);

        expect($invoiceNumber)->toBe('2025-INV-00001');
    });

    it('generates numbers with year month format', function () {
        $this->company->numbering_settings = [
            'vendor_bill' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_MONTH->value,
                'prefix' => 'BILL',
                'padding' => 3,
            ],
        ];
        $this->company->save();

        $date = Carbon::create(2025, 3, 10);
        $billNumber = $this->sequenceService->getNextVendorBillNumber($this->company, $date);

        expect($billNumber)->toBe('202503-BILL-001');
    });

    it('generates numbers with slash separated format', function () {
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SLASH_SEPARATED->value,
                'prefix' => 'INV',
                'padding' => 4,
            ],
        ];
        $this->company->save();

        $date = Carbon::create(2025, 12, 25);
        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company, $date);

        expect($invoiceNumber)->toBe('INV/2025/0001');
    });

    it('uses default settings when numbering_settings is null', function () {
        $this->company->numbering_settings = null;
        $this->company->save();

        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company);
        $billNumber = $this->sequenceService->getNextVendorBillNumber($this->company);

        expect($invoiceNumber)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($billNumber)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('maintains separate sequences for different document types', function () {
        $invoice1 = $this->sequenceService->getNextInvoiceNumber($this->company);
        $bill1 = $this->sequenceService->getNextVendorBillNumber($this->company);
        $invoice2 = $this->sequenceService->getNextInvoiceNumber($this->company);
        $bill2 = $this->sequenceService->getNextVendorBillNumber($this->company);

        expect($invoice1)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($bill1)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
        expect($invoice2)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($bill2)->toMatch('/^BILL\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('maintains separate sequences for different companies', function () {
        $company2 = Company::factory()->create();

        $company1Invoice = $this->sequenceService->getNextInvoiceNumber($this->company);
        $company2Invoice = $this->sequenceService->getNextInvoiceNumber($company2);

        expect($company1Invoice)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($company2Invoice)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('creates sequence records in database', function () {
        $this->sequenceService->getNextInvoiceNumber($this->company);

        $sequence = \Modules\Foundation\Models\Sequence::where([
            'company_id' => $this->company->id,
            'document_type' => 'invoice',
        ])->first();

        expect($sequence)->not->toBeNull();
        expect($sequence->prefix)->toBe('INV');
        expect($sequence->current_number)->toBe(1);
        expect($sequence->padding)->toBe(7);
    });

    it('uses current date when no date provided for date-based formats', function () {
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::YEAR_PREFIX->value,
                'prefix' => 'INV',
                'padding' => 5,
            ],
        ];
        $this->company->save();

        $currentYear = now()->year;
        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company);

        expect($invoiceNumber)->toBe("{$currentYear}-INV-00001");
    });
});
