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

class CreateJournalEntryForExpenseBillAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'lines.tax', 'vendor');

            $company = $vendorBill->company;
            $currency = $vendorBill->currency;

            // Use vendor's individual payable account if available, otherwise fall back to default
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;

            if (! $apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            $expenseLines = $vendorBill->lines->where('product.type', '!=', 'storable');

            $lineDTOs = [];
            $totalDebit = Money::of(0, $currency->code);

            foreach ($expenseLines as $line) {
                // If an asset category is provided, treat as asset acquisition
                if ($line->asset_category_id) {
                    $category = AssetCategory::find($line->asset_category_id);
                    if (! $category) {
                        throw new RuntimeException('Invalid asset category selected on bill line.');
                    }
                    // Dr Asset (subtotal), Dr Input Tax, Cr AP
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $category->asset_account_id,
                        debit: $line->subtotal,
                        credit: Money::of(0, $currency->code),
                        description: 'Asset: ' . $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalDebit = $totalDebit->plus($line->subtotal);

                    if ($line->tax_id && $line->total_line_tax->isPositive()) {
                        $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id;
                        if (! $taxAccountId) {
                            throw new InvalidArgumentException('Company default tax account is not configured');
                        }
                        $lineDTOs[] = new CreateJournalEntryLineDTO(
                            account_id: $taxAccountId,
                            debit: $line->total_line_tax,
                            credit: Money::of(0, $currency->code),
                            description: 'Input tax for asset: ' . $line->description,
                            partner_id: null,
                            analytic_account_id: null,
                        );
                        $totalDebit = $totalDebit->plus($line->total_line_tax);
                    }
                } else {
                    // Standard expense
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line->expense_account_id,
                        debit: $line->subtotal,
                        credit: Money::of(0, $currency->code),
                        description: $line->description,
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalDebit = $totalDebit->plus($line->subtotal);

                    // Handle tax if applicable
                    if ($line->tax_id && $line->total_line_tax->isPositive()) {
                        $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id; // Or a more specific tax account if needed
                        if (! $taxAccountId) {
                            throw new InvalidArgumentException('Company default tax account is not configured');
                        }
                        $lineDTOs[] = new CreateJournalEntryLineDTO(
                            account_id: $taxAccountId,
                            debit: $line->total_line_tax,
                            credit: Money::of(0, $currency->code),
                            description: "Tax for: {$line->description}",
                            partner_id: null,
                            analytic_account_id: null,
                        );
                        $totalDebit = $totalDebit->plus($line->total_line_tax);
                    }
                }
            }

            // Credit Accounts Payable for the total amount
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: Money::of(0, $currency->code),
                credit: $totalDebit,
                description: 'Accounts Payable',
                partner_id: $vendorBill->vendor_id,
                analytic_account_id: null,
            );

            if (! $company->default_purchase_journal_id) {
                throw new InvalidArgumentException('Company default purchase journal is not configured');
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $currency->id,
                entry_date: $vendorBill->accounting_date,
                reference: $vendorBill->bill_reference,
                description: 'Vendor Bill ' . $vendorBill->bill_reference,
                source_type: VendorBill::class,
                source_id: $vendorBill->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                exchange_rate: $vendorBill->exchange_rate_at_creation,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
