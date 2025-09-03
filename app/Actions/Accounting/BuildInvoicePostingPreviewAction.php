<?php

namespace App\Actions\Accounting;

use App\Models\Invoice;
use Brick\Money\Money;

class BuildInvoicePostingPreviewAction
{
    private function accountLabelName($account): string
    {
        if (! $account) {
            return '';
        }
        $name = is_array($account->name ?? null) ? ($account->name['en'] ?? reset($account->name)) : ($account->name ?? '');

        return $name;
    }

    public function execute(Invoice $invoice): array
    {
        $invoice->load('company', 'currency', 'customer', 'invoiceLines.tax.taxAccount', 'invoiceLines.incomeAccount');

        $issues = [];
        $errors = [];
        $company = $invoice->company;
        $currencyCode = $invoice->currency->code;

        $arAccountId = $invoice->customer->receivable_account_id ?? $company->default_accounts_receivable_id;
        if (! $arAccountId) {
            $msg = 'Company default Accounts Receivable account is not configured.';
            $errors[] = $msg;
            $issues[] = ['type' => 'ar_account_missing', 'message' => $msg];
        }
        if (! $company->default_sales_journal_id) {
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
            if (! $incomeAccountId) {
                $msg = 'Income account is missing on an invoice line.';
                $errors[] = $msg;
                $issues[] = ['type' => 'income_account_missing', 'message' => $msg, 'product_id' => $line->product_id];
            } else {
                $incomeAccount = $line->incomeAccount;
                $linesPreview[] = [
                    'account_id' => $incomeAccountId,
                    'account_name' => $this->accountLabelName($incomeAccount),
                    'account_code' => $incomeAccount?->code,
                    'debit_minor' => 0,
                    'credit_minor' => $line->subtotal->getMinorAmount()->toInt(),
                    'description' => 'Revenue: '.$line->description,
                ];
                $totalCredit = $totalCredit->plus($line->subtotal);
            }

            // Credit: tax account per line when applicable
            if ($line->total_line_tax->isPositive()) {
                $tax = $line->tax;
                $taxAccountId = $tax?->tax_account_id;
                if (! $taxAccountId) {
                    $msg = 'Selected tax does not have a tax account configured.';
                    $errors[] = $msg;
                    $issues[] = ['type' => 'tax_account_missing', 'message' => $msg, 'tax_id' => $tax?->id];
                } else {
                    $taxAccount = $tax?->taxAccount;
                    $linesPreview[] = [
                        'account_id' => $taxAccountId,
                        'account_name' => $this->accountLabelName($taxAccount),
                        'account_code' => $taxAccount?->code,
                        'debit_minor' => 0,
                        'credit_minor' => $line->total_line_tax->getMinorAmount()->toInt(),
                        'description' => 'Output tax: '.$line->description,
                    ];
                    $totalCredit = $totalCredit->plus($line->total_line_tax);
                }
            }
        }

        // Debit: Accounts Receivable total
        if ($arAccountId) {
            $arAccount = $invoice->customer->receivableAccount ?? $company->accountsReceivableAccount;
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
