<?php

namespace Modules\Accounting\Actions\Accounting;

use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\AssetCategory;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\User;
use Modules\Purchase\Models\VendorBill;
use RuntimeException;

class CreateJournalEntryForVendorBillAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly \Modules\Foundation\Services\CurrencyConverterService $currencyConverter,
    ) {
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'vendor', 'lines.product.stockInputAccount', 'lines.tax');

            $company = $vendorBill->company;
            $vendorBillCurrency = $vendorBill->currency;
            $companyCurrency = $company->currency; // Use company's functional currency for journal entry
            $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;
            if (! $apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            $lineDTOs = [];
            $totalAP = Money::of(0, $companyCurrency->code);

            // Helper function to convert amounts to company currency
            $convertToCompanyCurrency = function (Money $amount) use ($exchangeRate, $companyCurrency, $vendorBillCurrency) {
                if ($vendorBillCurrency->id === $companyCurrency->id) {
                    return $amount;
                }
                return $this->currencyConverter->convertWithRate(
                    $amount,
                    $exchangeRate,
                    $companyCurrency->code,
                    false
                );
            };

            foreach ($vendorBill->lines as $line) {
                $isStorable = $line->product?->type === \Modules\Product\Enums\Products\ProductType::Storable;
                $isAsset = (bool) $line->asset_category_id;

                // Determine if tax should be capitalized (non-recoverable) or treated as deductible
                $taxShouldBeCapitalized = false;
                $taxAmountCompanyCurrency = Money::of(0, $companyCurrency->code);
                if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax) {
                    $taxAmountCompanyCurrency = $convertToCompanyCurrency($line->total_line_tax);
                    $taxShouldBeCapitalized = !$line->tax->is_recoverable;
                }

                if ($isStorable && $line->product) {
                    $stockInputAccount = $line->product->stockInputAccount;
                    if (! $stockInputAccount) {
                        throw new RuntimeException("Product ID {$line->product_id} missing stock input account");
                    }
                    // Convert subtotal to company currency
                    $subtotalCompanyCurrency = $convertToCompanyCurrency($line->subtotal);

                    // If tax should be capitalized, add it to the inventory cost
                    $inventoryCost = $subtotalCompanyCurrency;
                    if ($taxShouldBeCapitalized) {
                        $inventoryCost = $inventoryCost->plus($taxAmountCompanyCurrency);
                    }

                    // Dr Stock Input (subtotal + capitalized tax) for Anglo-Saxon; receipt valuation handles Inventory Dr
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: (int) $stockInputAccount->getKey(),
                        debit: $inventoryCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $taxShouldBeCapitalized
                            ? "Stock Input (incl. tax): {$line->description}"
                            : "Stock Input: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->subtotal,
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalAP = $totalAP->plus($inventoryCost);
                } elseif ($isAsset) {
                    $category = AssetCategory::find($line->asset_category_id);
                    if (! $category) {
                        throw new RuntimeException('Invalid asset category on bill line.');
                    }
                    // Convert subtotal to company currency
                    $subtotalCompanyCurrency = $convertToCompanyCurrency($line->subtotal);

                    // If tax should be capitalized, add it to the asset cost
                    $assetCost = $subtotalCompanyCurrency;
                    if ($taxShouldBeCapitalized) {
                        $assetCost = $assetCost->plus($taxAmountCompanyCurrency);
                    }

                    // Dr Asset (subtotal + capitalized tax)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $category->asset_account_id,
                        debit: $assetCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $taxShouldBeCapitalized
                            ? "Asset (incl. tax): {$line->description}"
                            : "Asset: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->subtotal,
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalAP = $totalAP->plus($assetCost);
                } else {
                    // Convert subtotal to company currency
                    $subtotalCompanyCurrency = $convertToCompanyCurrency($line->subtotal);

                    // If tax should be capitalized, add it to the expense cost
                    $expenseCost = $subtotalCompanyCurrency;
                    if ($taxShouldBeCapitalized) {
                        $expenseCost = $expenseCost->plus($taxAmountCompanyCurrency);
                    }

                    // Expense line: Dr expense (subtotal + capitalized tax)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line->expense_account_id,
                        debit: $expenseCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $taxShouldBeCapitalized
                            ? "Expense (incl. tax): {$line->description}"
                            : $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->subtotal,
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalAP = $totalAP->plus($expenseCost);
                }

                // Taxes: Only create separate tax entries for recoverable taxes
                // Non-recoverable taxes are already capitalized into the cost above
                if ($line->tax_id && $line->total_line_tax->isPositive() && !$taxShouldBeCapitalized) {
                    $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id;
                    if (! $taxAccountId) {
                        throw new RuntimeException('Default input tax account not configured for company.');
                    }
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $taxAccountId,
                        debit: $taxAmountCompanyCurrency,
                        credit: Money::of(0, $companyCurrency->code),
                        description: 'Input tax: ' . $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->total_line_tax,
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalAP = $totalAP->plus($taxAmountCompanyCurrency);
                }
            }

            // Credit AP once
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: Money::of(0, $companyCurrency->code),
                credit: $totalAP,
                description: 'Accounts Payable',
                partner_id: $vendorBill->vendor_id,
                analytic_account_id: null,
                original_currency_amount: $vendorBill->total_amount,
                exchange_rate_at_transaction: $exchangeRate,
            );

            if (! $company->default_purchase_journal_id) {
                throw new InvalidArgumentException('Company default purchase journal is not configured');
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $companyCurrency->id, // Use company's functional currency
                entry_date: $vendorBill->accounting_date,
                reference: $vendorBill->bill_reference,
                description: 'Vendor Bill ' . $vendorBill->bill_reference,
                source_type: VendorBill::class,
                source_id: $vendorBill->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                exchange_rate: $exchangeRate, // Pass the exchange rate for reference
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
