<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Contracts\VendorBillJournalEntryCreatorContract;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\AssetCategory;
use Modules\Accounting\Models\JournalEntry;
use Modules\Purchase\Models\VendorBill;
use RuntimeException;

class CreateJournalEntryForVendorBillAction implements VendorBillJournalEntryCreatorContract
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
        private readonly \Modules\Foundation\Services\CurrencyConverterService $currencyConverter,
    ) {}

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

                // Determine tax breakdown (capitalized vs recoverable)
                $capitalizedTaxAmount = Money::of(0, $companyCurrency->code);
                $recoverableTaxComponents = []; // Array of ['tax' => Tax, 'amount' => Money]

                if ($line->tax_id && $line->total_line_tax->isPositive() && $line->tax) {
                    $tax = $line->tax;
                    $totalLineTax = $convertToCompanyCurrency($line->total_line_tax);

                    if ($tax->is_group) {
                        $children = $tax->children;
                        // Note: allocate() requires integer ratios, so we scale decimal rates (e.g., 0.10)
                        // to integers by multiplying by 10000 to support rates with up to 4 decimal places
                        $ratios = $children->map(fn ($t) => (int) ($t->rate * 10000))->toArray();

                        if (array_sum($ratios) > 0) {
                            $allocatedAmounts = $totalLineTax->allocate(...$ratios);
                            $index = 0;
                            foreach ($children as $childTax) {
                                $amount = $allocatedAmounts[$index];
                                if (! $childTax->is_recoverable) {
                                    $capitalizedTaxAmount = $capitalizedTaxAmount->plus($amount);
                                } else {
                                    $recoverableTaxComponents[] = ['tax' => $childTax, 'amount' => $amount];
                                }
                                $index++;
                            }
                        }
                    } else {
                        // Single Tax
                        if (! $tax->is_recoverable) {
                            $capitalizedTaxAmount = $totalLineTax;
                        } else {
                            $recoverableTaxComponents[] = ['tax' => $tax, 'amount' => $totalLineTax];
                        }
                    }
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
                    if ($capitalizedTaxAmount->isPositive()) {
                        $inventoryCost = $inventoryCost->plus($capitalizedTaxAmount);
                    }

                    // Dr Stock Input (subtotal + capitalized tax) for Anglo-Saxon; receipt valuation handles Inventory Dr
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: (int) $stockInputAccount->getKey(),
                        debit: $inventoryCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $capitalizedTaxAmount->isPositive()
                            ? "Stock Input (incl. capitalized tax): {$line->description}"
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
                    if ($capitalizedTaxAmount->isPositive()) {
                        $assetCost = $assetCost->plus($capitalizedTaxAmount);
                    }

                    // Dr Asset (subtotal + capitalized tax)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $category->asset_account_id,
                        debit: $assetCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $capitalizedTaxAmount->isPositive()
                            ? "Asset (incl. capitalized tax): {$line->description}"
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
                    if ($capitalizedTaxAmount->isPositive()) {
                        $expenseCost = $expenseCost->plus($capitalizedTaxAmount);
                    }

                    // Expense line: Dr expense (subtotal + capitalized tax)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line->expense_account_id,
                        debit: $expenseCost,
                        credit: Money::of(0, $companyCurrency->code),
                        description: $capitalizedTaxAmount->isPositive()
                            ? "Expense (incl. capitalized tax): {$line->description}"
                            : $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                        original_currency_amount: $line->subtotal,
                        exchange_rate_at_transaction: $exchangeRate,
                    );
                    $totalAP = $totalAP->plus($expenseCost);
                }

                // Taxes: Create separate tax entries for recoverable taxes
                foreach ($recoverableTaxComponents as $component) {
                    $tax = $component['tax'];
                    $amount = $component['amount'];

                    if ($amount->isPositive()) {
                        // Prefer Tax specific account, fallback to company default
                        $taxAccountId = $tax->tax_account_id ?? ($company->default_tax_receivable_id ?? $company->default_tax_account_id);

                        if (! $taxAccountId) {
                            throw new RuntimeException("Tax account not configured for tax '{$tax->name}' and no default company input tax account set.");
                        }

                        $lineDTOs[] = new CreateJournalEntryLineDTO(
                            account_id: $taxAccountId,
                            debit: $amount,
                            credit: Money::of(0, $companyCurrency->code),
                            description: $tax->is_group ? "Input tax (Split): {$line->description}" : "Input tax: {$line->description}",
                            partner_id: null,
                            analytic_account_id: null,
                            // Note: original_currency_amount is complex for splits, skipping for simplicity unless critical
                            exchange_rate_at_transaction: $exchangeRate,
                            tax_id: $tax->id,
                        );
                        $totalAP = $totalAP->plus($amount);
                    }
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
                description: 'Vendor Bill '.$vendorBill->bill_reference,
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
