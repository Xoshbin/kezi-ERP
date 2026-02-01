<?php

namespace Jmeryar\Inventory\Listeners\Purchase;

use Jmeryar\Inventory\Actions\GoodsReceipt\CreateGoodsReceiptFromPurchaseOrderAction;
use Jmeryar\Inventory\DataTransferObjects\ReceiveGoodsFromPurchaseOrderDTO;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Purchase\Events\PurchaseOrderConfirmed;

/**
 * Creates a Goods Receipt (StockPicking) when a Purchase Order is confirmed.
 *
 * This listener ensures that inventory receiving is decoupled from billing:
 * - A draft StockPicking is always created for tracking goods receipt
 * - The picking must be validated before inventory is updated
 * - VendorBill posting validates that goods have been received (for PO-linked bills)
 */
class CreateStockPickingForPurchaseOrder
{
    public function __construct(
        private readonly CreateGoodsReceiptFromPurchaseOrderAction $createAction,
    ) {}

    /**
     * Handle the PurchaseOrderConfirmed event.
     *
     * Creates a draft StockPicking (GRN) for all storable products in the PO.
     * The InventoryAccountingMode setting now only affects whether GRN validation
     * is strictly required before VendorBill posting.
     */
    public function handle(PurchaseOrderConfirmed $event): void
    {
        $po = $event->purchaseOrder;

        // Check if PO has storable products
        $hasStorableProducts = $po->lines->contains(function ($line) {
            return $line->product && $line->product->type === ProductType::Storable;
        });

        if (! $hasStorableProducts) {
            return; // No storable products, no GRN needed
        }

        // Create the GRN using the action
        $dto = new ReceiveGoodsFromPurchaseOrderDTO(
            purchaseOrder: $po,
            userId: auth()->id() ?? $po->created_by_user_id ?? \App\Models\User::first()->id,
            receiptDate: $po->expected_delivery_date ?? $po->po_date,
        );

        $this->createAction->execute($dto);
    }
}
