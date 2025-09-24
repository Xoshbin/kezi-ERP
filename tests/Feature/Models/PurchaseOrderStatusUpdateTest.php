<?php

use App\Enums\Purchases\PurchaseOrderStatus;
use App\Models\Partner;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

describe('Purchase Order Status Updates', function () {
    beforeEach(function () {
        $this->setupWithConfiguredCompany();
        $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    });

    it('prevents receive status updates when not from inventory operation', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Add a line with some received quantity to simulate partial receipt
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 10,
            'quantity_received' => 5, // Partially received
        ]);

        $purchaseOrder->load('lines');

        // Verify that isPartiallyReceived would return true
        expect($purchaseOrder->isPartiallyReceived())->toBeTrue();

        // Call updateStatusBasedOnReceipts without fromInventoryOperation flag
        $purchaseOrder->updateStatusBasedOnReceipts(fromInventoryOperation: false);

        // Status should NOT change to PartiallyReceived
        expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Confirmed);
    });

    it('allows receive status updates when from inventory operation', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Add a line with some received quantity to simulate partial receipt
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 10,
            'quantity_received' => 5, // Partially received
        ]);

        $purchaseOrder->load('lines');

        // Verify that isPartiallyReceived would return true
        expect($purchaseOrder->isPartiallyReceived())->toBeTrue();

        // Call updateStatusBasedOnReceipts WITH fromInventoryOperation flag
        $purchaseOrder->updateStatusBasedOnReceipts(fromInventoryOperation: true);

        // Status SHOULD change to PartiallyReceived
        expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::PartiallyReceived);
    });

    it('allows to receive transition without inventory flag', function () {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'created_by_user_id' => $this->user->id,
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        // Add a line with NO received quantity
        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 10,
            'quantity_received' => 0, // Nothing received yet
        ]);

        $purchaseOrder->load('lines');

        // Call updateStatusBasedOnReceipts without fromInventoryOperation flag
        $purchaseOrder->updateStatusBasedOnReceipts(fromInventoryOperation: false);

        // Status SHOULD change to ToReceive (this is allowed)
        expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ToReceive);
    });
});
