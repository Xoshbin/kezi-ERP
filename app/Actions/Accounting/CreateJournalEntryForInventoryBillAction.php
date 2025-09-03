<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForInventoryBillAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'vendor', 'lines.product.inventoryAccount');

            $company = $vendorBill->company;
            $currency = $vendorBill->currency;

            $storableLines = $vendorBill->lines->where('product.type', 'storable');

            if ($storableLines->isEmpty()) {
                throw new RuntimeException('This action should only be called for bills with storable items.');
            }

            // Determine Accounts Payable account: vendor-specific or company default
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;
            if (! $apAccountId) {
                throw new RuntimeException('Default Accounts Payable account is not configured for this company.');
            }

            $lineDTOs = [];
            $totalAPCredit = Money::of(0, $currency->code);

            foreach ($storableLines as $line) {
                $inventoryAccount = $line->product->inventoryAccount;
                if (! $inventoryAccount) {
                    throw new RuntimeException("Product ID {$line->product_id} is missing default inventory account.");
                }

                // Debit Inventory for net amount (exclude deductible tax)
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $inventoryAccount->id,
                    debit: $line->subtotal,
                    credit: Money::of(0, $currency->code),
                    description: "Inventory valuation for: {$line->description}",
                    partner_id: null,
                    analytic_account_id: null,
                );
                $totalAPCredit = $totalAPCredit->plus($line->subtotal);

                // If tax exists, debit tax receivable (deductible input VAT) and include in AP
                if ($line->tax_id && $line->total_line_tax->isPositive()) {
                    $taxAccountId = $company->default_tax_receivable_id ?? $company->default_tax_account_id;
                    if (! $taxAccountId) {
                        throw new RuntimeException('Default tax account is not configured for this company.');
                    }
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $taxAccountId,
                        debit: $line->total_line_tax,
                        credit: Money::of(0, $currency->code),
                        description: "Input tax for: {$line->description}",
                        partner_id: null,
                        analytic_account_id: null,
                    );
                    $totalAPCredit = $totalAPCredit->plus($line->total_line_tax);
                }
            }

            // Credit Accounts Payable for the total value
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $apAccountId,
                debit: Money::of(0, $currency->code),
                credit: $totalAPCredit,
                description: 'Accounts Payable for storable items',
                partner_id: $vendorBill->vendor_id,
                analytic_account_id: null,
            );

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $currency->id,
                entry_date: $vendorBill->accounting_date,
                reference: 'BILL/'.$vendorBill->bill_reference,
                description: 'Inventory purchase (AP recognition) for Bill '.$vendorBill->bill_reference,
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
