<?php

namespace Kezi\Accounting\Listeners\Asset;

use App\Models\Company;
use Brick\Money\Money;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Kezi\Accounting\Actions\Assets\CreateAssetAction;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;
use Kezi\Foundation\Services\CurrencyConverterService;
use Kezi\Purchase\Models\VendorBillLine;

class CreateAssetFromVendorBillListener implements ShouldQueue
{
    public function __construct(
        private readonly CreateAssetAction $createAssetAction,
        private readonly CurrencyConverterService $currencyConverter,
    ) {}

    public function handle(\Kezi\Purchase\Events\VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;
        /** @var \App\Models\Company $company */
        $company = $vendorBill->company;

        // Eager load relationships for efficiency
        $vendorBill->loadMissing('lines.expenseAccount', 'lines.product', 'currency', 'company.currency');

        /** @var \Kezi\Foundation\Models\Currency $companyCurrency */
        $companyCurrency = $company->currency;
        $billCurrency = $vendorBill->currency;

        // Determine exchange rate: use bill's stored rate or fetch latest
        /** @var float|null $storedRate */
        $storedRate = $vendorBill->getAttribute('exchange_rate_at_creation');
        $exchangeRate = (float) ($storedRate ?? 1.0);

        if ($billCurrency->id !== $companyCurrency->id && ! $storedRate) {
            $exchangeRate = $this->currencyConverter->getExchangeRate($billCurrency, $vendorBill->bill_date, $company)
                ?? $this->currencyConverter->getLatestExchangeRate($billCurrency, $company)
                ?? 1.0;
        }

        foreach ($vendorBill->lines as $line) {
            // Check explicit asset-category selection first, fallback to can_create_assets on account
            /** @var mixed $category */
            $category = null;
            if ($line->asset_category_id) {
                $category = \Kezi\Accounting\Models\AssetCategory::find($line->asset_category_id);
            } elseif ($line->expenseAccount->can_create_assets) {
                // Implicit asset via account; map into a temporary category-like structure using company defaults
                $category = new class($company, $line)
                {
                    public int $asset_account_id;

                    public int $depreciation_expense_account_id;

                    public int $accumulated_depreciation_account_id;

                    public int $useful_life_years;

                    public DepreciationMethod $depreciation_method;

                    public bool $prorata_temporis;

                    public ?float $declining_factor;

                    public function __construct(public Company $company, public VendorBillLine $line)
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
                        $this->prorata_temporis = (bool) ($this->line->product->prorata_temporis ?? false);
                        $this->declining_factor = $this->line->product->declining_factor ?? null;
                    }
                };
            }

            if (! $category) {
                continue; // Not an asset line
            }

            $purchaseValue = $this->currencyConverter->convertWithRate(
                $line->subtotal,
                $exchangeRate,
                $companyCurrency->code,
                false
            );

            // Prevent creation of assets for zero or negative values
            if ($purchaseValue->isZero() || $purchaseValue->isNegative()) {
                continue;
            }

            try {
                // Extract values to variables to avoid PHPStan confusion on mixed/$category type access
                $usefulLife = $category->useful_life_years ?? 5;
                $depMethod = $category->depreciation_method ?? DepreciationMethod::StraightLine;
                $assetAccId = $category->asset_account_id;
                $depExpAccId = $category->depreciation_expense_account_id;
                $accumDepAccId = $category->accumulated_depreciation_account_id;
                $prorata = $category->prorata_temporis ?? false;
                $declining = $category->declining_factor ?? null;

                $assetDTO = new \Kezi\Accounting\DataTransferObjects\Assets\CreateAssetDTO(
                    company_id: $vendorBill->company_id,
                    name: $line->product->name ?? $line->description,
                    purchase_date: $vendorBill->bill_date,
                    purchase_value: $purchaseValue,
                    salvage_value: Money::zero($purchaseValue->getCurrency()),
                    useful_life_years: $usefulLife,
                    depreciation_method: $depMethod,
                    asset_account_id: $assetAccId,
                    depreciation_expense_account_id: $depExpAccId,
                    accumulated_depreciation_account_id: $accumDepAccId,
                    currency_id: $vendorBill->currency_id,
                    prorata_temporis: $prorata,
                    declining_factor: $declining,
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
