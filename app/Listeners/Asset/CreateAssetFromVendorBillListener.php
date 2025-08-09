<?php
// FILE: app/Listeners/CreateAssetFromVendorBillListener.php

namespace App\Listeners\Asset;

use App\Actions\Assets\CreateAssetAction;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Enums\Assets\DepreciationMethod;
use App\Events\VendorBillConfirmed;
use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateAssetFromVendorBillListener implements ShouldQueue
{
    public function __construct(private readonly CreateAssetAction $createAssetAction)
    {
    }

    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;
        $company = $vendorBill->company;

        // Eager load relationships for efficiency
        $vendorBill->loadMissing('lines.expenseAccount', 'lines.product', 'currency');

        foreach ($vendorBill->lines as $line) {
            // Check if the line's account is configured to create assets
            if ($line->expenseAccount?->can_create_assets) {
                try {
                    // Determine fallback accounts if company-specific defaults are not configured.
                    $deprExpenseAccountId = $company->default_depreciation_expense_account_id
                        ?? $company->default_sales_discount_account_id // expense-type fallback
                        ?? $company->default_tax_account_id // another expense-like fallback
                        ?? $line->expense_account_id; // last resort

                    $accumDeprAccountId = $company->default_accumulated_depreciation_account_id
                        ?? $company->default_outstanding_receipts_account_id // asset-type fallback
                        ?? $company->default_accounts_receivable_id // asset-type fallback
                        ?? $line->expense_account_id; // last resort

                    // Gather data for the asset from the vendor bill line
                    $assetDTO = new CreateAssetDTO(
                        company_id: $vendorBill->company_id,
                        name: $line->product->name ?? $line->description,
                        purchase_date: $vendorBill->bill_date,
                        purchase_value: (string) $line->subtotal->getAmount(), // Pass major units; MoneyCast will convert to minor
                        salvage_value: 0, // Default to 0, can be adjusted later
                        useful_life_years: $line->product?->useful_life_years ?? 5, // Default or from product
                        depreciation_method: $line->product?->depreciation_method ?? DepreciationMethod::StraightLine, // Default or from product
                        asset_account_id: $line->expense_account_id, // The trigger account is the asset account
                        depreciation_expense_account_id: $deprExpenseAccountId,
                        accumulated_depreciation_account_id: $accumDeprAccountId,
                        currency_id: $vendorBill->currency_id,
                        source_type: get_class($vendorBill),
                        source_id: $vendorBill->id
                    );

                    $this->createAssetAction->execute($assetDTO);

                } catch (\Exception $e) {
                    Log::error('Failed to create asset from vendor bill line.', [
                        'vendor_bill_id' => $vendorBill->id,
                        'vendor_bill_line_id' => $line->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
