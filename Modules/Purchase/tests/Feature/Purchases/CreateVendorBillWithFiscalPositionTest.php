<?php

namespace Modules\Purchase\Tests\Feature\Purchases;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPosition;
use Modules\Accounting\Models\FiscalPositionTaxMapping;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Actions\Purchases\CreateVendorBillAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('CreateVendorBill with Fiscal Position', function () {

    beforeEach(function () {
        $this->expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
        $this->currency = $this->company->currency;
    });

    it('automatically assigns fiscal position to vendor bill based on vendor', function () {
        $fp = FiscalPosition::factory()->for($this->company)->create([
            'name' => 'Iraq FP',
            'country' => 'IQ',
            'auto_apply' => true,
            'country' => 'IQ', // explicitly set to match partner
        ]);

        $vendor = Partner::factory()->for($this->company)->create([
            'country' => 'IQ',
            'fiscal_position_id' => null,
        ]);

        $dto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $vendor->id,
            currency_id: $this->currency->id,
            bill_reference: 'V-TEST',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            lines: [],
            created_by_user_id: $this->user->id,
            fiscal_position_id: null
        );

        $vendorBill = app(CreateVendorBillAction::class)->execute($dto);

        expect($vendorBill->fiscal_position_id)->toBe($fp->id);
    });

    it('automatically maps taxes on vendor bill lines based on fiscal position', function () {
        $fp = FiscalPosition::factory()->for($this->company)->create(['country' => null]);

        $originalTax = Tax::factory()->for($this->company)->create(['name' => '15% VAT', 'rate' => 15]);
        $mappedTax = Tax::factory()->for($this->company)->create(['name' => '0% Export', 'rate' => 0]);

        FiscalPositionTaxMapping::factory()->create([
            'company_id' => $this->company->id,
            'fiscal_position_id' => $fp->id,
            'original_tax_id' => $originalTax->id,
            'mapped_tax_id' => $mappedTax->id,
        ]);

        $vendor = Partner::factory()->for($this->company)->create([
            'fiscal_position_id' => $fp->id,
        ]);

        $lineDto = new CreateVendorBillLineDTO(
            product_id: null,
            description: 'Test Item',
            quantity: 1,
            unit_price: Money::of(100, $this->currency->code),
            expense_account_id: $this->expenseAccount->id,
            tax_id: $originalTax->id,
            analytic_account_id: null
        );

        $dto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $vendor->id,
            currency_id: $this->currency->id,
            bill_reference: 'V-TEST-2',
            bill_date: now()->format('Y-m-d'),
            accounting_date: now()->format('Y-m-d'),
            due_date: null,
            lines: [$lineDto],
            created_by_user_id: $this->user->id,
            fiscal_position_id: $fp->id
        );

        $vendorBill = app(CreateVendorBillAction::class)->execute($dto);
        $vendorBillLine = $vendorBill->lines()->first();

        // The observer should have swapped the tax
        expect($vendorBillLine->tax_id)->toBe($mappedTax->id);
    });
});
