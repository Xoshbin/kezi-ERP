<?php

namespace App\Observers;

use RuntimeException;
use Illuminate\Support\Facades\DB;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Enums\Purchases\VendorBillStatus;
use App\Models\StockMove;
use App\Models\VendorBill;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class VendorBillObserver
{
    /**
     * Handle the VendorBill "updated" event.
     */
    public function updated(VendorBill $vendorBill): void
    {
        // Only trigger when the status is first changed to 'posted'.
        if ($vendorBill->wasChanged('status') && $vendorBill->status === VendorBillStatus::Posted) {

            foreach ($vendorBill->lines as $line) {
                // Only process lines with storable products.
                if ($line->product?->type === ProductType::Storable) {
                    $this->processStorableProductLine($vendorBill, $line);
                }
            }
        }
    }

    public function processStorableProductLine(VendorBill $vendorBill, $line): void
    {
        if (!$line->product) {
            return;
        }

        $product = $line->product;
        $company = $vendorBill->company->fresh();

        if (!$company->vendorLocation || !$company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
        }

        $currency = $vendorBill->currency;

        // Create the physical stock move record
        StockMove::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'quantity' => $line->quantity,
            'from_location_id' => $company->vendorLocation->id,
            'to_location_id' => $company->defaultStockLocation->id,
            'source_type' => get_class($vendorBill),
            'source_id' => $vendorBill->id,
            'completed_at' => now(),
            'move_date' => $vendorBill->accounting_date,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Done,
            'created_by_user_id' => $vendorBill->user_id,
        ]);

        // Recalculate Average Cost (AVCO)
        $purchaseValue = $line->unit_price->multipliedBy($line->quantity);
        $oldValue = ($product->average_cost ?? Money::zero($currency->code))->multipliedBy($product->quantity_on_hand);
        $totalQuantity = $product->quantity_on_hand + $line->quantity;
        $totalValue = $oldValue->plus($purchaseValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::zero($currency->code);

        // Bypass Eloquent and update the database directly.
        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'quantity_on_hand' => $totalQuantity,
                'average_cost' => $newAverageCost->getMinorAmount()->toInt(),
            ]);
    }
}
