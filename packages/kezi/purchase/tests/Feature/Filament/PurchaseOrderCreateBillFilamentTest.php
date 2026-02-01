<?php

namespace Kezi\Purchase\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Partner;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Additional setup for vendor bill creation
    $this->company->update([
        'default_accounts_payable_id' => Account::factory()->for($this->company)->create(['type' => 'payable'])->id,
        'default_purchase_journal_id' => Journal::factory()->for($this->company)->create(['type' => 'purchase'])->id,
    ]);

    $this->vendor = Partner::factory()->for($this->company)->create([
        'type' => \Kezi\Foundation\Enums\Partners\PartnerType::Vendor,
    ]);
});

describe('PurchaseOrder Filament Create Bill Action', function () {
    it('can create a vendor bill from the purchase order edit page', function () {
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'vendor_id' => $this->vendor->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $po->refresh()->calculateTotalsFromLines();
        $po->save();

        Livewire::test(EditPurchaseOrder::class, [
            'record' => $po->getRouteKey(),
        ])
            ->assertActionVisible('create_bill')
            ->callAction('create_bill')
            ->assertHasNoActionErrors()
            ->assertNotified();

        $po->refresh();
        expect($po->status)->toBe(PurchaseOrderStatus::PartiallyBilled);
        expect($po->vendorBills)->toHaveCount(1);

        $vendorBill = $po->vendorBills->first();
        expect($vendorBill->bill_date->isToday())->toBeTrue();
    });

    it('hides the create bill action when PO has bills and canCreateBill logic prevents it', function () {
        // Current logic in PurchaseOrder::canCreateBill() is !hasBills()
        $po = PurchaseOrder::factory()->for($this->company)->create([
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $po->refresh()->calculateTotalsFromLines();
        $po->save();

        // Initially visible
        Livewire::test(EditPurchaseOrder::class, ['record' => $po->getRouteKey()])
            ->assertActionVisible('create_bill');

        // Create a bill manually to mock existing bill
        app(\Kezi\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction::class)->execute(
            new \Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO(
                purchase_order_id: $po->id,
                bill_reference: 'EXISTING',
                bill_date: now()->format('Y-m-d'),
                accounting_date: now()->format('Y-m-d'),
                due_date: null,
                created_by_user_id: $this->user->id,
                copy_all_lines: true
            )
        );

        $po->refresh();
        expect($po->hasBills())->toBeTrue();
        expect($po->canCreateBill())->toBeFalse();

        Livewire::test(EditPurchaseOrder::class, ['record' => $po->getRouteKey()])
            ->assertActionHidden('create_bill');
    });
});
