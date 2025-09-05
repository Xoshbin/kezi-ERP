<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Enums\Products\ProductType;
use App\Models\AssetCategory;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForVendorBillAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'vendor', 'lines.product.inventoryAccount');

            $company = $vendorBill->company;
            $currency = $vendorBill->currency;
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;
            if (! $apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            $lineDTOs = [];
            $totalAP = Money::of(0, $currency->code);

            foreach ($vendorBill->lines as $line) {
                $isStorable = $line->product?->type === ProductType::Storable;
                $isAsset = (bool) $line->asset_category_id;

                if ($isStorable && $line->product) {
                    $inventoryAccount = $line->product->inventoryAccount;
                    if (! $inventoryAccount) {
                        throw new RuntimeException("Product ID {$line->product_id} missing inventory account");
                    }
                    // Dr Inventory (subtotal)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: (int) $inventoryAccount->getKey(),
                        debit: $line->subtotal,
                        credit: Money::of(0, $currency->code),
                        description: "Inventory: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalAP = $totalAP->plus($line->subtotal);
                } elseif ($isAsset) {
                    $category = AssetCategory::find($line->asset_category_id);
                    if (! $category) {
                        throw new RuntimeException('Invalid asset category on bill line.');
                    }
                    // Dr Asset (subtotal)
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $category->asset_account_id,
                        debit: $line->subtotal,
                        credit: Money::of(0, $currency->code),
                        description: 'Asset: '.$line->description,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalAP = $totalAP->plus($line->subtotal);
                } else {
                    // Expense line: Dr expense
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line->expense_account_id,
                        debit: $line->subtotal,
                        credit: Money::of(0, $currency->code),
                        description: $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalAP = $totalAP->plus($line->subtotal);
                }

                // Taxes (deductible): debit input tax, include in AP
                if ($line->tax_id && $line->total_line_tax->isPositive()) {
                    $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id;
                    if (! $taxAccountId) {
                        throw new RuntimeException('Default input tax account not configured for company.');
                    }
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $taxAccountId,
                        debit: $line->total_line_tax,
                        credit: Money::of(0, $currency->code),
                        description: 'Input tax: '.$line->description,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalAP = $totalAP->plus($line->total_line_tax);
                }
            }

            // Credit AP once
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: Money::of(0, $currency->code),
                credit: $totalAP,
                description: 'Accounts Payable',
                partner_id: $vendorBill->vendor_id,
                analytic_account_id: null,
            );

            if (!$company->default_purchase_journal_id) {
                throw new \InvalidArgumentException('Company default purchase journal is not configured');
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $currency->id,
                entry_date: $vendorBill->accounting_date,
                reference: $vendorBill->bill_reference,
                description: 'Vendor Bill '.$vendorBill->bill_reference,
                source_type: VendorBill::class,
                source_id: $vendorBill->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
