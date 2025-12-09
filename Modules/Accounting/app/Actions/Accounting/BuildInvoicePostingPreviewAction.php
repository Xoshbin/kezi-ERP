<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Modules\Accounting\Models\Account;
use Modules\Sales\Models\Invoice;

class BuildInvoicePostingPreviewAction
{
    private function accountLabelName(?Account $account): string
    {
        if (!$account) {
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
     * Execute the invoice posting preview action.
     *
     * @return array{errors: array<int, string>, issues: array<int, array{type: string, message: string, product_id?: int|null, tax_id?: int|null}>, lines: array<int, array{account_id: int|null, account_name: string, account_code: string|null, debit_minor: int, credit_minor: int, description: string}>, totals: array{debit_minor: int, credit_minor: int, balanced: bool}}
     */
    public function execute(Invoice $invoice): array
    {
        $invoice->load('company', 'currency', 'customer', 'invoiceLines.tax.taxAccount', 'invoiceLines.incomeAccount');

        $issues = [];
        $errors = [];
        $company = $invoice->company;
        $currencyCode = $invoice->currency->code;

        // Validate business rules similar to vendor bills
        if ($invoice->invoiceLines->isEmpty()) {
            $msg = __('sales::invoice.validation_no_line_items');
            $errors[] = $msg;
            $issues[] = ['type' => 'no_line_items', 'message' => $msg];
        }

        if ($invoice->total_amount->isZero()) {
            $msg = __('sales::invoice.validation_zero_total_amount');
            $errors[] = $msg;
            $issues[] = ['type' => 'zero_total_amount', 'message' => $msg];
        }

        $arAccountId = $invoice->customer->receivable_account_id ?? $company->default_accounts_receivable_id;
        if (!$arAccountId) {
            $msg = 'Company default Accounts Receivable account is not configured.';
            $errors[] = $msg;
            $issues[] = ['type' => 'ar_account_missing', 'message' => $msg];
        }
        if (!$company->default_sales_journal_id) {
            $msg = 'Company default sales journal is not configured.';
            $errors[] = $msg;
            $issues[] = ['type' => 'sales_journal_missing', 'message' => $msg];
        }

        $linesPreview = [];
        $totalDebit = Money::of(0, $currencyCode);
        $totalCredit = Money::of(0, $currencyCode);

        foreach ($invoice->invoiceLines as $line) {
            // Credit: income account per line
            $incomeAccountId = $line->income_account_id;
            if (!$incomeAccountId) {
                $msg = 'Income account is missing on an invoice line.';
                $errors[] = $msg;
                $issues[] = ['type' => 'income_account_missing', 'message' => $msg, 'product_id' => $line->product_id];
            } else {
                /** @var Account|null $incomeAccount */
                $incomeAccount = $line->incomeAccount;
                $linesPreview[] = [
                    'account_id' => $incomeAccountId,
                    'account_name' => $this->accountLabelName($incomeAccount),
                    'account_code' => $incomeAccount?->code,
                    'debit_minor' => 0,
                    'credit_minor' => $line->subtotal->getMinorAmount()->toInt(),
                    'description' => 'Revenue: ' . $line->description,
                ];
                $totalCredit = $totalCredit->plus($line->subtotal);
            }

            // Credit: tax account per line when applicable
            if ($line->total_line_tax->isPositive()) {
                $tax = $line->tax;
                $taxAccountId = $tax?->tax_account_id;
                if (!$taxAccountId) {
                    $msg = 'Selected tax does not have a tax account configured.';
                    $errors[] = $msg;
                    $issues[] = ['type' => 'tax_account_missing', 'message' => $msg, 'tax_id' => $tax?->id];
                } else {
                    $taxAccount = $tax->taxAccount;
                    $linesPreview[] = [
                        'account_id' => $taxAccountId,
                        'account_name' => $this->accountLabelName($taxAccount),
                        'account_code' => $taxAccount->code,
                        'debit_minor' => 0,
                        'credit_minor' => $line->total_line_tax->getMinorAmount()->toInt(),
                        'description' => 'Output tax: ' . $line->description,
                    ];
                    $totalCredit = $totalCredit->plus($line->total_line_tax);
                }
            }
        }

        // Debit: Accounts Receivable total
        if ($arAccountId) {
            /** @var Account|null $arAccount */
            $arAccount = $invoice->customer->receivableAccount ?? $company->defaultAccountsReceivable;
            $linesPreview[] = [
                'account_id' => $arAccountId,
                'account_name' => $this->accountLabelName($arAccount),
                'account_code' => $arAccount?->code,
                'debit_minor' => $totalCredit->getMinorAmount()->toInt(),
                'credit_minor' => 0,
                'description' => 'Accounts Receivable',
            ];
            $totalDebit = $totalCredit;
        }

        return [
            'errors' => $errors,
            'issues' => $issues,
            'lines' => $linesPreview,
            'totals' => [
                'debit_minor' => $totalDebit->getMinorAmount()->toInt(),
                'credit_minor' => $totalCredit->getMinorAmount()->toInt(),
                'balanced' => $totalDebit->isEqualTo($totalCredit),
            ],
        ];
    }
}
