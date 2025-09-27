<?php

namespace Modules\Accounting\Listeners\Asset;

use App\Actions\Assets\CreateAssetAction;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Enums\Assets\DepreciationMethod;
use App\Events\VendorBillConfirmed;
use App\Models\AssetCategory;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateAssetFromVendorBillListener implements ShouldQueue
{
    public function __construct(private readonly CreateAssetAction $createAssetAction) {}

    public function handle(\Modules\Purchase\Events\VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;
        $company = $vendorBill->company;

        // Eager load relationships for efficiency
        $vendorBill->loadMissing('lines.expenseAccount', 'lines.product', 'currency');

        foreach ($vendorBill->lines as $line) {
            // Check explicit asset-category selection first, fallback to can_create_assets on account
            $category = null;
            if ($line->asset_category_id) {
                $category = \Modules\Accounting\Models\AssetCategory::find($line->asset_category_id);
            } elseif ($line->expenseAccount->can_create_assets) {
                // Implicit asset via account; map into a temporary category-like structure using company defaults
                $category = new class($company, $line)
                {
                    public int $asset_account_id;

                    public int $depreciation_expense_account_id;

                    public int $accumulated_depreciation_account_id;

                    public int $useful_life_years;

                    public \App\Enums\Assets\DepreciationMethod $depreciation_method;

                    public function __construct(public \App\Models\Company $company, public \App\Models\VendorBillLine $line)
                    {
                        $this->asset_account_id = $this->line->expense_account_id;
                        $this->depreciation_expense_account_id = $this->company->default_depreciation_expense_account_id
                            ?? $this->company->default_sales_discount_account_id
                            ?? $this->company->default_tax_account_id
                            ?? $this->line->expense_account_id;
                        $this->accumulated_depreciation_account_id = $this->company->default_accumulated_depreciation_account_id
                            ?? $this->company->default_outstanding_receipts_account_id
                            ?? $this->company->default_accounts_receivable_id
                            ?? $this->line->expense_account_id;
                        $this->useful_life_years = $this->line->product->useful_life_years ?? 5;
                        $this->depreciation_method = $this->line->product->depreciation_method ?? DepreciationMethod::StraightLine;
                    }

                    public function __get(string $name): mixed
                    {
                        return match ($name) {
                            'asset_account_id' => $this->asset_account_id,
                            'depreciation_expense_account_id' => $this->depreciation_expense_account_id,
                            'accumulated_depreciation_account_id' => $this->accumulated_depreciation_account_id,
                            'useful_life_years' => $this->useful_life_years,
                            'depreciation_method' => $this->depreciation_method,
                            default => null,
                        };
                    }
                };
            }

            if (! $category) {
                continue; // Not an asset line
            }

            try {
                $assetDTO = new \Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO(
                    company_id: $vendorBill->company_id,
                    name: $line->product->name ?? $line->description,
                    purchase_date: $vendorBill->bill_date,
                    purchase_value: (int) $line->subtotal->getAmount()->toFloat(),
                    salvage_value: 0,
                    useful_life_years: $category->useful_life_years,
                    depreciation_method: $category->depreciation_method,
                    asset_account_id: $category->asset_account_id,
                    depreciation_expense_account_id: $category->depreciation_expense_account_id,
                    accumulated_depreciation_account_id: $category->accumulated_depreciation_account_id,
                    currency_id: $vendorBill->currency_id,
                    source_type: get_class($vendorBill),
                    source_id: $vendorBill->id
                );

                $this->createAssetAction->execute($assetDTO);

            } catch (Exception $e) {
                Log::error('Failed to create asset from vendor bill line.', [
                    'vendor_bill_id' => $vendorBill->id,
                    'vendor_bill_line_id' => $line->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
