<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AssetCategory;
use Modules\Purchase\Models\VendorBill;

class BuildVendorBillPostingPreviewAction
{
    private function accountLabelName(?Account $account): string
    {
        if (! $account) {
            return '';
        }
        // Some models may cast name as array (translatable); normalize to string
        $raw = $account->name ?? '';
        if (is_array($raw)) {
            $name = $raw['en'] ?? (empty($raw) ? '' : (string) array_values($raw)[0]);
        } else {
            $name = (string) $raw;
        }

        return $name;
    }

    /**
     * Execute the vendor bill posting preview action.
     *
     * @return array{errors: array<int, string>, issues: array<int, array{type: string, message: string, product_id?: int|null}>, lines: array<int, array{account_id: int|null, account_name: string, account_code: string|null, debit_minor: int, credit_minor: int, description: string, product_id?: int|null}>, totals: array{debit_minor: int, credit_minor: int, balanced: bool}}
     */
    public function execute(VendorBill $vendorBill): array
    {
        $vendorBill->load('company', 'currency', 'vendor', 'lines.product.inventoryAccount');

        $errors = [];
        $issues = [];
        $company = $vendorBill->company;
        $currencyCode = $vendorBill->currency->code;

        // Validate that vendor bill has line items
        if ($vendorBill->lines->isEmpty()) {
            $msg = __('vendor_bill.validation_no_line_items');
            $errors[] = $msg;
            $issues[] = ['type' => 'no_line_items', 'message' => $msg];
        }

        // Validate that vendor bill has non-zero total amount
        if ($vendorBill->total_amount->isZero()) {
            $msg = __('vendor_bill.validation_zero_total_amount');
            $errors[] = $msg;
            $issues[] = ['type' => 'zero_total_amount', 'message' => $msg];
        }

        $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;
        if (! $apAccountId) {
            $msg = 'Company default Accounts Payable account is not configured.';
            $errors[] = $msg;
            $issues[] = ['type' => 'ap_account_missing', 'message' => $msg];
        }
        if (! $company->default_purchase_journal_id) {
            $msg = 'Company default purchase journal is not configured.';
            $errors[] = $msg;
            $issues[] = ['type' => 'purchase_journal_missing', 'message' => $msg];
        }

        $linesPreview = [];
        $debitTotal = Money::of(0, $currencyCode);
        $creditTotal = Money::of(0, $currencyCode);

        foreach ($vendorBill->lines as $line) {
            $isStorable = $line->product?->type === \Modules\Product\Enums\Products\ProductType::Storable;
            $isAsset = (bool) $line->asset_category_id;

            if ($isStorable && $line->product) {
                /** @var Account|null $inventoryAccount */
                $inventoryAccount = $line->product->inventoryAccount;
                if (! $inventoryAccount) {
                    $msg = "Product ID {$line->product_id} is missing its inventory account.";
                    $errors[] = $msg;
                    $issues[] = ['type' => 'inventory_account_missing', 'message' => $msg, 'product_id' => $line->product_id];
                } else {
                    $linesPreview[] = [
                        'account_id' => $inventoryAccount->id,
                        'account_name' => $this->accountLabelName($inventoryAccount),
                        'account_code' => $inventoryAccount->code,
                        'debit_minor' => $line->subtotal->getMinorAmount()->toInt(),
                        'credit_minor' => 0,
                        'description' => 'Inventory: ' . $line->description,
                        'product_id' => $line->product_id,
                    ];
                    $debitTotal = $debitTotal->plus($line->subtotal);
                }
            } elseif ($isAsset) {
                $category = AssetCategory::find($line->asset_category_id);
                if (! $category) {
                    $msg = 'Invalid asset category selected on a bill line.';
                    $errors[] = $msg;
                    $issues[] = ['type' => 'asset_category_invalid', 'message' => $msg];
                } else {
                    /** @var Account|null $assetAccount */
                    $assetAccount = $category->assetAccount;
                    $linesPreview[] = [
                        'account_id' => $category->asset_account_id,
                        'account_name' => $this->accountLabelName($assetAccount),
                        'account_code' => $assetAccount instanceof Account ? $assetAccount->code : null,
                        'debit_minor' => $line->subtotal->getMinorAmount()->toInt(),
                        'credit_minor' => 0,
                        'description' => 'Asset: ' . $line->description,
                    ];
                    $debitTotal = $debitTotal->plus($line->subtotal);
                }
            } else {
                /** @var Account|null $expenseAccount */
                $expenseAccount = $line->expenseAccount;
                $linesPreview[] = [
                    'account_id' => $line->expense_account_id,
                    'account_name' => $this->accountLabelName($expenseAccount),
                    'account_code' => $expenseAccount?->code,
                    'debit_minor' => $line->subtotal->getMinorAmount()->toInt(),
                    'credit_minor' => 0,
                    'description' => $line->description, // may be array via translations; view normalizes
                ];
                $debitTotal = $debitTotal->plus($line->subtotal);
            }

            if ($line->tax_id && $line->total_line_tax->isPositive()) {
                $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id;
                if (! $taxAccountId) {
                    $msg = 'Company input tax account is not configured but taxable lines exist.';
                    $errors[] = $msg;
                    $issues[] = ['type' => 'input_tax_missing', 'message' => $msg];
                } else {
                    /** @var Account|null $taxAccount */
                    $taxAccount = $company->defaultTaxReceivable ?? $company->defaultTaxAccount;
                    $linesPreview[] = [
                        'account_id' => $taxAccountId,
                        'account_name' => $this->accountLabelName($taxAccount),
                        'account_code' => $taxAccount?->code,
                        'debit_minor' => $line->total_line_tax->getMinorAmount()->toInt(),
                        'credit_minor' => 0,
                        'description' => 'Input tax: ' . $line->description, // may be array via translations; view normalizes
                    ];
                    $debitTotal = $debitTotal->plus($line->total_line_tax);
                }
            }
        }

        if ($apAccountId) {
            /** @var Account|null $apAccount */
            $apAccount = $vendorBill->vendor->payableAccount ?? $company->defaultAccountsPayable;
            $linesPreview[] = [
                'account_id' => $apAccountId,
                'account_name' => $this->accountLabelName($apAccount),
                'account_code' => $apAccount?->code,
                'debit_minor' => 0,
                'credit_minor' => $debitTotal->getMinorAmount()->toInt(),
                'description' => 'Accounts Payable',
            ];
            $creditTotal = $debitTotal;
        }

        return [
            'errors' => $errors,
            'issues' => $issues,
            'lines' => $linesPreview,
            'totals' => [
                'debit_minor' => $debitTotal->getMinorAmount()->toInt(),
                'credit_minor' => $creditTotal->getMinorAmount()->toInt(),
                'balanced' => $debitTotal->isEqualTo($creditTotal),
            ],
        ];
    }
}
