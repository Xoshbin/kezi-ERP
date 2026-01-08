<?php

namespace Modules\Purchase\Services;

use Brick\Money\Money;
use Illuminate\Support\Collection;
use Modules\Foundation\Enums\Incoterm;
use Modules\Purchase\DataTransferObjects\Purchases\ShippingCostValidationResult;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

class ShippingCostAllocationService
{
    /**
     * Validate shipping costs on a vendor bill against its Incoterm.
     */
    public function validateVendorBillShippingCosts(VendorBill $bill): ShippingCostValidationResult
    {
        $incoterm = $bill->incoterm;

        if (! $incoterm) {
            return new ShippingCostValidationResult(
                hasWarnings: false,
                warnings: [],
                unexpectedCosts: [],
                totalShippingCosts: Money::zero($bill->currency->code)
            );
        }

        $shippingLines = $this->getShippingLines($bill);
        $totalShippingCosts = Money::zero($bill->currency->code);
        $warnings = [];
        $unexpectedCosts = [];

        foreach ($shippingLines as $line) {
            $totalShippingCosts = $totalShippingCosts->plus($line->subtotal);

            if ($line->shipping_cost_type && ! $incoterm->shouldBuyerPayFor($line->shipping_cost_type)) {
                $costTypeName = $line->shipping_cost_type->getLabel();
                $warning = __(':type charges may not be appropriate for :incoterm - seller typically pays for these costs.', [
                    'type' => $costTypeName,
                    'incoterm' => $incoterm->name,
                ]);

                if (! in_array($warning, $warnings)) {
                    $warnings[] = $warning;
                }

                $unexpectedCosts[$costTypeName] = ($unexpectedCosts[$costTypeName] ?? Money::zero($bill->currency->code))->plus($line->subtotal);
            }
        }

        return new ShippingCostValidationResult(
            hasWarnings: count($warnings) > 0,
            warnings: $warnings,
            unexpectedCosts: $unexpectedCosts,
            totalShippingCosts: $totalShippingCosts
        );
    }

    /**
     * Get all lines identified as shipping costs for a vendor bill.
     *
     * @return Collection<int, VendorBillLine>
     */
    public function getShippingLines(VendorBill $bill): Collection
    {
        return $bill->lines->filter(function (VendorBillLine $line) {
            return $line->shipping_cost_type !== null;
        });
    }

    /**
     * Get expected cost allocation for a given Incoterm.
     */
    public function getExpectedAllocation(Incoterm $incoterm): array
    {
        return [
            'freight' => ! $incoterm->sellerPaysFreight() ? __('Buyer') : __('Seller'),
            'insurance' => ! $incoterm->sellerPaysInsurance() ? __('Buyer') : __('Seller'),
            'export_clearance' => ! $incoterm->sellerHandlesExportClearance() ? __('Buyer') : __('Seller'),
            'import_clearance' => ! $incoterm->sellerHandlesImportClearance() ? __('Buyer') : __('Seller'),
        ];
    }
}
