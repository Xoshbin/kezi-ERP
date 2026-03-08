<?php

namespace Kezi\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Purchase\Models\VendorBill;
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
                throw new RuntimeException(__('accounting::exceptions.inventory_bill.only_storable_items'));
            }

            // Determine Accounts Payable account: vendor-specific or company default
            $apAccountId = $vendorBill->vendor->payable_account_id ?? $company->default_accounts_payable_id;
            if (! $apAccountId) {
                throw new RuntimeException(__('accounting::exceptions.common.default_accounts_payable_missing'));
            }

            $lineDTOs = [];
            $totalAPCredit = Money::of(0, $currency->code);

            foreach ($storableLines as $line) {
                if (! $line->product) {
                    throw new RuntimeException(__('accounting::exceptions.common.product_missing_for_line', ['id' => $line->id]));
                }
                $inventoryAccount = $line->product->inventoryAccount;
                if (! $inventoryAccount) {
                    throw new RuntimeException(__('accounting::exceptions.inventory_bill.product_missing_inventory_account', ['id' => $line->product_id]));
                }

                // Debit Inventory for net amount (exclude deductible tax)
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: (int) $inventoryAccount->getKey(),
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
                        throw new RuntimeException(__('accounting::exceptions.common.default_tax_account_missing'));
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

            if (! $company->default_purchase_journal_id) {
                throw new InvalidArgumentException(__('accounting::exceptions.common.default_purchase_journal_missing'));
            }

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
                exchange_rate: $vendorBill->exchange_rate_at_creation,
            );

            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
