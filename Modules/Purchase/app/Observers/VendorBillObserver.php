<?php

namespace Modules\Purchase\Observers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Models\VendorBill;
use RuntimeException;

class VendorBillObserver
{
    /**
     * Handle the VendorBill "updated" event.
     */
    public function updated(VendorBill $vendorBill): void
    {
        // Only trigger when the status is first changed to 'posted'.
        // Business logic for creating stock moves now lives in VendorBillService::post().
        // Observers must not create moves or update valuation; keep side effects only.
        if ($vendorBill->wasChanged('status') && $vendorBill->status === VendorBillStatus::Posted) {
            // No-op by design
        }
    }

    public function processStorableProductLine(VendorBill $vendorBill, VendorBillLine $line): void
    {
        if (! $line->product) {
            return;
        }

        // Load tax relationship to check if it should be capitalized
        $line->load('tax');

        $product = $line->product;
        $company = $vendorBill->company->fresh();
        if (! $company) {
            throw new RuntimeException('Failed to refresh company for vendor bill');
        }

        if (! $company->vendorLocation || ! $company->defaultStockLocation) {
            throw new RuntimeException("Default Vendor or Stock Location is not configured for Company ID: {$company->id}.");
        }

        // Create the physical stock move record
        StockMove::create([
            'company_id' => $company->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => $line->quantity,
            'from_location_id' => $company->vendorLocation->getKey(),
            'to_location_id' => $company->defaultStockLocation->getKey(),
            'source_type' => get_class($vendorBill),
            'source_id' => $vendorBill->getKey(),
            'completed_at' => now(),
            'move_date' => $vendorBill->accounting_date,
            'move_type' => StockMoveType::Incoming,
            'status' => StockMoveStatus::Done,
            'created_by_user_id' => (int) $vendorBill->user_id,
        ]);

        // Recalculate Average Cost (AVCO) using company currency amounts for consistency
        // Get the company's base currency for cost calculations
        $companyCurrency = $company->currency;
        $costCurrency = $companyCurrency->code;

        // Use company currency amounts if available, otherwise convert on the fly
        if ($line->unit_price_company_currency) {
            $unitPriceInCompanyCurrency = $line->unit_price_company_currency;
        } else {
            // Convert to company currency if not already converted
            if ($vendorBill->currency_id === $company->currency_id) {
                $unitPriceInCompanyCurrency = $line->unit_price;
            } else {
                // For foreign currency, use the exchange rate to convert
                $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;
                $unitPriceInCompanyCurrency = Money::of(
                    $line->unit_price->getAmount()->toFloat() * $exchangeRate,
                    $costCurrency
                );
            }
        }

        // Include capitalized tax in the unit cost if tax is non-recoverable
        if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax && !$line->tax->is_recoverable) {
            // Convert tax to company currency if needed
            $taxInCompanyCurrency = $line->total_line_tax_company_currency ?? $line->total_line_tax;
            if (!$line->total_line_tax_company_currency && $vendorBill->currency_id !== $company->currency_id) {
                $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;
                $taxInCompanyCurrency = Money::of(
                    $line->total_line_tax->getAmount()->toFloat() * $exchangeRate,
                    $costCurrency
                );
            }

            // Add tax to unit price for cost calculation
            $unitPriceInCompanyCurrency = $unitPriceInCompanyCurrency->plus(
                $taxInCompanyCurrency->dividedBy($line->quantity)
            );
        }

        $purchaseValue = $unitPriceInCompanyCurrency->multipliedBy($line->quantity);
        $oldValue = ($product->average_cost ?? Money::zero($costCurrency))->multipliedBy($product->quantity_on_hand);
        $totalQuantity = (float) $product->quantity_on_hand + (float) $line->quantity;
        $totalValue = $oldValue->plus($purchaseValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::zero($costCurrency);

        // Bypass Eloquent and update the database directly.
        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'quantity_on_hand' => $totalQuantity,
                'average_cost' => $newAverageCost->getMinorAmount()->toInt(),
            ]);
    }
}
