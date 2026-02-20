<?php

namespace Kezi\Purchase\Observers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockMove;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Purchase\Services\VendorBillService;
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

        // Get StockQuantService to update quantities at location level
        $stockQuantService = app(\Kezi\Inventory\Services\Inventory\StockQuantService::class);

        // Update StockQuant for destination location - this is now the source of truth
        $stockQuantService->adjust(
            $company->id,
            $product->id,
            $company->defaultStockLocation->id,
            $line->quantity
        );

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
                $unitPriceInCompanyCurrency = app(\Kezi\Foundation\Services\CurrencyConverterService::class)->convertWithRate(
                    $line->unit_price,
                    $exchangeRate,
                    $costCurrency,
                    false
                );
            }
        }

        // Include capitalized tax in the unit cost if tax is non-recoverable
        if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax && ! $line->tax->is_recoverable) {
            // Convert tax to company currency if needed
            $taxInCompanyCurrency = $line->total_line_tax_company_currency ?? $line->total_line_tax;
            if (! $line->total_line_tax_company_currency && $vendorBill->currency_id !== $company->currency_id) {
                $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;
                $taxInCompanyCurrency = app(\Kezi\Foundation\Services\CurrencyConverterService::class)->convertWithRate(
                    $line->total_line_tax,
                    $exchangeRate,
                    $costCurrency,
                    false
                );
            }

            // Add tax to unit price for cost calculation
            $unitPriceInCompanyCurrency = $unitPriceInCompanyCurrency->plus(
                $taxInCompanyCurrency->dividedBy($line->quantity)
            );
        }

        // Calculate AVCO using StockQuant totals as source of truth
        // Note: We need to get the quantity BEFORE this line was added (current - this line)
        $currentTotalQuantity = $stockQuantService->getTotalQuantity($company->id, $product->id);
        $previousQuantity = $currentTotalQuantity - $line->quantity;

        $purchaseValue = $unitPriceInCompanyCurrency->multipliedBy($line->quantity);
        $oldValue = ($product->average_cost ?? Money::zero($costCurrency))->multipliedBy($previousQuantity);
        $totalValue = $oldValue->plus($purchaseValue);

        $newAverageCost = $currentTotalQuantity > 0
            ? $totalValue->dividedBy($currentTotalQuantity, RoundingMode::HALF_UP)
            : Money::zero($costCurrency);

        // Update only average_cost - quantity is now managed by StockQuant
        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'average_cost' => $newAverageCost->getMinorAmount()->toInt(),
            ]);
    }
}
