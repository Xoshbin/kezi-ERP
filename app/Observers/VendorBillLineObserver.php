<?php

namespace App\Observers;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use App\Models\VendorBillLine;

class VendorBillLineObserver
{
    /**
     * Handle the VendorBillLine "creating" event.
     */
    public function creating(VendorBillLine $vendorBillLine): void
    {
        // 1. Calculate the subtotal using the Money object's method.
        // We use the unit_price (Money) and multiply it by the quantity.
        $subtotal = $vendorBillLine->unit_price->multipliedBy($vendorBillLine->quantity, RoundingMode::HALF_UP);
        $vendorBillLine->subtotal = $subtotal;

        // 2. Calculate the tax amount.
        // Start with a zero Money object in the same currency.
        $taxAmount = Money::zero($subtotal->getCurrency());

        // If a tax relationship exists on the line, calculate the tax.
        if ($vendorBillLine->tax) {
            // Assuming the 'rate' on your Tax model is a Money object.
            $taxAmount = $subtotal->multipliedBy($vendorBillLine->tax->rate, RoundingMode::HALF_UP);
        }

        $vendorBillLine->total_line_tax = $taxAmount;
    }

    /**
     * Handle the "saved" event for the VendorBillLine.
     * This event fires on both creation and update.
     */
    public function created(VendorBillLine $vendorBillLine): void
    {
        $vendorBillLine->vendorBill->calculateTotalsFromLines();
        $vendorBillLine->vendorBill->saveQuietly();
    }

    /**
     * Handle the VendorBillLine "updated" event.
     */
    public function updated(VendorBillLine $vendorBillLine): void
    {
        $vendorBillLine->vendorBill->calculateTotalsFromLines();
        $vendorBillLine->vendorBill->saveQuietly();
    }

    /**
     * Handle the "deleted" event for the VendorBillLine.
     */
    public function deleted(VendorBillLine $vendorBillLine): void
    {
        $vendorBillLine->vendorBill->calculateTotalsFromLines();
        $vendorBillLine->vendorBill->saveQuietly();
    }

    /**
     * Handle the VendorBillLine "restored" event.
     */
    public function restored(VendorBillLine $vendorBillLine): void
    {
        //
    }

    /**
     * Handle the VendorBillLine "force deleted" event.
     */
    public function forceDeleted(VendorBillLine $vendorBillLine): void
    {
        //
    }
}
