<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\FiscalPosition;
use Kezi\Sales\Models\Invoice;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('InvoiceResource Fiscal Position', function () {

    beforeEach(function () {
        $this->incomeAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
    });

    it('can set fiscal position on invoice', function () {
        $invoice = Invoice::factory()->for($this->company)->create();
        $fp = FiscalPosition::factory()->for($this->company)->create(['name' => 'Foreign']);

        Livewire::test(EditInvoice::class, [
            'record' => $invoice->id,
        ])
            ->fillForm([
                'fiscal_position_id' => $fp->id,
                'invoiceLines' => [
                    [
                        'description' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'income_account_id' => $this->incomeAccount->id,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($invoice->fresh()->fiscal_position_id)->toBe($fp->id);
    });
});
