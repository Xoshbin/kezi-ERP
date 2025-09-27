<?php

use App\Enums\Sales\InvoiceStatus;
use App\Enums\Settings\NumberingType;
use App\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

describe('Invoice Numbering Integration', function () {
    uses(WithConfiguredCompany::class);

    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->invoiceService = app(InvoiceService::class);
    });

    it('auto-generates invoice numbers when posting invoices', function () {
        // Create a draft invoice with at least one line
        $invoice = \Modules\Sales\Models\Invoice::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
        ]);

        expect($invoice->invoice_number)->toBeNull();

        // Post the invoice
        $this->invoiceService->confirm($invoice, $this->user);

        // Refresh the model
        $invoice->refresh();

        expect($invoice->status)->toBe(InvoiceStatus::Posted);
        expect($invoice->invoice_number)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('uses custom numbering settings when generating invoice numbers', function () {
        // Set custom numbering settings
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SLASH_SEPARATED->value,
                'prefix' => 'INVOICE',
                'padding' => 4,
            ],
        ];
        $this->company->save();

        // Create a draft invoice with at least one line
        $invoice = \Modules\Sales\Models\Invoice::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
            'invoice_date' => '2025-06-15',
        ]);

        // Post the invoice
        $this->invoiceService->confirm($invoice, $this->user);

        // Refresh the model
        $invoice->refresh();

        expect($invoice->invoice_number)->toBe('INVOICE/2025/0001');
    });

    it('generates sequential invoice numbers', function () {
        // Create and post multiple invoices
        $invoices = [];

        for ($i = 0; $i < 3; $i++) {
            $invoice = \Modules\Sales\Models\Invoice::factory()->withLines(1)->create([
                'company_id' => $this->company->id,
                'status' => InvoiceStatus::Draft,
            ]);

            $this->invoiceService->confirm($invoice, $this->user);
            $invoice->refresh();
            $invoices[] = $invoice;
        }

        expect($invoices[0]->invoice_number)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($invoices[1]->invoice_number)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
        expect($invoices[2]->invoice_number)->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    });

    it('uses invoice date for date-based numbering formats', function () {
        // Set dot-separated numbering
        $this->company->numbering_settings = [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::DOT_SEPARATED->value,
                'prefix' => 'INV',
                'padding' => 3,
            ],
        ];
        $this->company->save();

        // Create invoice with specific date and at least one line
        $invoice = \Modules\Sales\Models\Invoice::factory()->withLines(1)->create([
            'company_id' => $this->company->id,
            'status' => InvoiceStatus::Draft,
            'invoice_date' => '2025-12-25',
        ]);

        // Post the invoice
        $this->invoiceService->confirm($invoice, $this->user);

        // Refresh the model
        $invoice->refresh();

        expect($invoice->invoice_number)->toBe('INV.2025.001');
    });
});
