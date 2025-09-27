<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;

class BuildAdjustmentPostingPreviewAction
{
    private function accountLabelName(?\Modules\Accounting\Models\Account $account): string
    {
        if (! $account) {
            return '';
        }
        $raw = $account->name ?? '';
        if (is_array($raw)) {
            $name = $raw['en'] ?? (empty($raw) ? '' : (string) array_values($raw)[0]);
        } else {
            $name = (string) $raw;
        }

        return $name;
    }

    /**
     * Execute the adjustment posting preview action.
     *
     * @return array{errors: array<int, string>, issues: array<int, array{type: string, message: string}>, lines: array<int, array{account_id: int|null, account_name: string, account_code: string|null, debit_minor: int, credit_minor: int, description: string}>, totals: array{debit_minor: int, credit_minor: int, balanced: bool}}
     */
    public function execute(\Modules\Inventory\Models\AdjustmentDocument $adjustment): array
    {
        $adjustment->load('company', 'currency');

        $issues = [];
        $errors = [];
        $company = $adjustment->company;
        $currencyCode = $adjustment->currency->code;

        $arAccountId = $company->default_accounts_receivable_id;
        $salesDiscountId = $company->default_sales_discount_account_id;
        $taxAccountId = $company->default_tax_account_id;
        $salesJournalId = $company->default_sales_journal_id;

        foreach ([
            ['cond' => ! $arAccountId, 'type' => 'ar_account_missing', 'msg' => 'Company default Accounts Receivable account is not configured.'],
            ['cond' => ! $salesDiscountId, 'type' => 'sales_discount_missing', 'msg' => 'Company default Sales Discount account is not configured.'],
            ['cond' => ! $taxAccountId, 'type' => 'tax_payable_missing', 'msg' => 'Company default Tax Payable account is not configured.'],
            ['cond' => ! $salesJournalId, 'type' => 'sales_journal_missing', 'msg' => 'Company default sales journal is not configured.'],
        ] as $check) {
            if ($check['cond']) {
                $errors[] = $check['msg'];
                $issues[] = ['type' => $check['type'], 'message' => $check['msg']];
            }
        }

        $linesPreview = [];
        $debit = Money::of(0, $currencyCode);
        $credit = Money::of(0, $currencyCode);

        $totalAmount = $adjustment->total_amount ?? Money::of(0, $currencyCode);
        $totalTax = $adjustment->total_tax ?? Money::of(0, $currencyCode);
        $subtotal = $totalAmount->minus($totalTax);

        if ($salesDiscountId) {
            /** @var \Modules\Accounting\Models\Account|null $salesDiscount */
            $salesDiscount = $company->defaultSalesDiscountAccount;
            $linesPreview[] = [
                'account_id' => $salesDiscountId,
                'account_name' => $this->accountLabelName($salesDiscount),
                'account_code' => $salesDiscount?->code,
                'debit_minor' => $subtotal->getMinorAmount()->toInt(),
                'credit_minor' => 0,
                'description' => 'Sales Discount/Contra-Revenue',
            ];
            $debit = $debit->plus($subtotal);
        }

        if ($totalTax->isPositive() && $taxAccountId) {
            /** @var \Modules\Accounting\Models\Account|null $taxAccount */
            $taxAccount = $company->defaultTaxAccount;
            $linesPreview[] = [
                'account_id' => $taxAccountId,
                'account_name' => $this->accountLabelName($taxAccount),
                'account_code' => $taxAccount?->code,
                'debit_minor' => $totalTax->getMinorAmount()->toInt(),
                'credit_minor' => 0,
                'description' => 'Tax Payable',
            ];
            $debit = $debit->plus($totalTax);
        }

        if ($arAccountId) {
            /** @var \Modules\Accounting\Models\Account|null $ar */
            $ar = $company->defaultAccountsReceivable;
            $linesPreview[] = [
                'account_id' => $arAccountId,
                'account_name' => $this->accountLabelName($ar),
                'account_code' => $ar?->code,
                'debit_minor' => 0,
                'credit_minor' => $totalAmount->getMinorAmount()->toInt(),
                'description' => 'Accounts Receivable',
            ];
            $credit = $totalAmount;
        }

        return [
            'errors' => $errors,
            'issues' => $issues,
            'lines' => $linesPreview,
            'totals' => [
                'debit_minor' => $debit->getMinorAmount()->toInt(),
                'credit_minor' => $credit->getMinorAmount()->toInt(),
                'balanced' => $debit->isEqualTo($credit),
            ],
        ];
    }
}
