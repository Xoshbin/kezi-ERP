<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPosition;
use Modules\Purchase\Models\VendorBill;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('VendorBillResource Fiscal Position', function () {

    beforeEach(function () {
        $this->expenseAccount = Account::factory()->for($this->company)->create(['type' => 'expense']);
    });

    it('can set fiscal position on vendor bill', function () {
        $vendorBill = VendorBill::factory()->for($this->company)->create();
        $fp = FiscalPosition::factory()->for($this->company)->create(['name' => 'Foreign']);

        Livewire::test(EditVendorBill::class, [
            'record' => $vendorBill->id,
        ])
            ->fillForm([
                'fiscal_position_id' => $fp->id,
                'lines' => [
                    [
                        'description' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'expense_account_id' => $this->expenseAccount->id,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($vendorBill->fresh()->fiscal_position_id)->toBe($fp->id);
    });
});
