<?php

namespace Modules\Purchase\tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Services\Inventory\GoodsReceiptService;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Enums\Purchases\ThreeWayMatchStatus;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\ThreeWayMatchingService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    $this->vendor = Partner::factory()->vendor()->create([
        'company_id' => $this->company->id,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $this->service = app(ThreeWayMatchingService::class);
});

describe('ThreeWayMatchingService - Status Determination', function () {
    it('returns NotApplicable for bill without PO', function () {
        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'purchase_order_id' => null,
        ]);

        $status = $this->service->getMatchingStatus($bill);

        expect($status)->toBe(ThreeWayMatchStatus::NotApplicable);
    });

    it('returns PendingReceipt when PO exists but no GRN validated', function () {
        // Mock the GoodsReceiptService to return false for hasValidatedGoodsReceipt
        $this->mock(GoodsReceiptService::class, function ($mock) {
            $mock->shouldReceive('hasValidatedGoodsReceipt')->andReturn(false);
        });

        $po = PurchaseOrder::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => PurchaseOrderStatus::ToReceive,
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'quantity_received' => 0,
        ]);

        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'purchase_order_id' => $po->id,
        ]);

        $service = app(ThreeWayMatchingService::class);
        $status = $service->getMatchingStatus($bill);

        expect($status)->toBe(ThreeWayMatchStatus::PendingReceipt);
    });
});

describe('ThreeWayMatchingService - Update Status', function () {
    it('updates three_way_match_status on vendor bill', function () {
        $bill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'purchase_order_id' => null,  // Will be NotApplicable
            'three_way_match_status' => null,
        ]);

        $this->service->updateMatchStatus($bill);

        $bill->refresh();
        expect($bill->three_way_match_status)->toBe(ThreeWayMatchStatus::NotApplicable);
    });
});

describe('ThreeWayMatchStatus Enum', function () {
    it('has correct colors', function () {
        expect(ThreeWayMatchStatus::FullyMatched->color())
            ->toBe('success');
        expect(ThreeWayMatchStatus::PendingReceipt->color())
            ->toBe('warning');
        expect(ThreeWayMatchStatus::QuantityMismatch->color())
            ->toBe('danger');
        expect(ThreeWayMatchStatus::PartiallyReceived->color())
            ->toBe('info');
        expect(ThreeWayMatchStatus::NotApplicable->color())
            ->toBe('gray');
    });

    it('PendingReceipt blocks posting', function () {
        expect(ThreeWayMatchStatus::PendingReceipt->blocksPosting())
            ->toBeTrue();
    });

    it('FullyMatched does not block posting', function () {
        expect(ThreeWayMatchStatus::FullyMatched->blocksPosting())
            ->toBeFalse();
    });

    it('NotApplicable does not block posting', function () {
        expect(ThreeWayMatchStatus::NotApplicable->blocksPosting())
            ->toBeFalse();
    });

    it('QuantityMismatch has mismatch flag', function () {
        expect(ThreeWayMatchStatus::QuantityMismatch->hasMismatch())
            ->toBeTrue();
    });

    it('PriceMismatch has mismatch flag', function () {
        expect(ThreeWayMatchStatus::PriceMismatch->hasMismatch())
            ->toBeTrue();
    });

    it('FullyMatched has no mismatch flag', function () {
        expect(ThreeWayMatchStatus::FullyMatched->hasMismatch())
            ->toBeFalse();
    });
});
