<?php

namespace Kezi\Sales\Tests\Feature\Sales;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalPosition;
use Kezi\Accounting\Models\FiscalPositionTaxMapping;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Partner;
use Kezi\Sales\Actions\Sales\CreateInvoiceAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('CreateInvoice with Fiscal Position', function () {

    beforeEach(function () {
        $this->incomeAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
        $this->currency = $this->company->currency;
    });

    it('automatically assigns fiscal position to invoice based on customer', function () {
        $fp = FiscalPosition::factory()->for($this->company)->create([
            'name' => 'Iraq FP',
            'country' => 'IQ',
            'auto_apply' => true,
        ]);

        $customer = Partner::factory()->for($this->company)->create([
            'country' => 'IQ',
            'fiscal_position_id' => null,
        ]);

        $dto = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $customer->id,
            currency_id: $this->currency->id,
            invoice_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'),
            lines: [],
            fiscal_position_id: null
        );

        $invoice = app(CreateInvoiceAction::class)->execute($dto);

        expect($invoice->fiscal_position_id)->toBe($fp->id);
    });

    it('automatically maps taxes on invoice lines based on fiscal position', function () {
        $fp = FiscalPosition::factory()->for($this->company)->create(['country' => null]);

        $originalTax = Tax::factory()->for($this->company)->create(['name' => '15% VAT', 'rate' => 15]);
        $mappedTax = Tax::factory()->for($this->company)->create(['name' => '0% Export', 'rate' => 0]);

        FiscalPositionTaxMapping::factory()->create([
            'company_id' => $this->company->id,
            'fiscal_position_id' => $fp->id,
            'original_tax_id' => $originalTax->id,
            'mapped_tax_id' => $mappedTax->id,
        ]);

        $customer = Partner::factory()->for($this->company)->create([
            'fiscal_position_id' => $fp->id,
        ]);

        $lineDto = new CreateInvoiceLineDTO(
            description: 'Test Item',
            quantity: 1,
            unit_price: Money::of(100, $this->currency->code),
            income_account_id: $this->incomeAccount->id,
            product_id: null,
            tax_id: $originalTax->id
        );

        $dto = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $customer->id,
            currency_id: $this->currency->id,
            invoice_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'),
            lines: [$lineDto],
            fiscal_position_id: $fp->id
        );

        $invoice = app(CreateInvoiceAction::class)->execute($dto);
        $invoiceLine = $invoice->invoiceLines()->first();

        // The observer should have swapped the tax
        expect($invoiceLine->tax_id)->toBe($mappedTax->id);
    });
});
