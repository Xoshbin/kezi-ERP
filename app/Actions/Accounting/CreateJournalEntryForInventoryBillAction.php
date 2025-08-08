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
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(VendorBill $vendorBill, User $user): JournalEntry
    {
        return DB::transaction(function () use ($vendorBill, $user) {
            $vendorBill->load('company', 'currency', 'lines.product.inventoryAccount', 'lines.product.stockInputAccount');

            $company = $vendorBill->company;
            $currency = $vendorBill->currency;

            $storableLines = $vendorBill->lines->where('product.type', 'storable');

            if ($storableLines->isEmpty()) {
                throw new RuntimeException('This action should only be called for bills with storable items.');
            }

            $lineDTOs = [];
            $totalValue = Money::of(0, $currency->code);

            foreach ($storableLines as $line) {
                $inventoryAccount = $line->product->inventoryAccount;
                $stockInputAccount = $line->product->stockInputAccount;

                if (!$inventoryAccount || !$stockInputAccount) {
                    throw new RuntimeException("Product ID {$line->product_id} is missing default inventory or stock input accounts.");
                }

                $lineValue = $line->subtotal->plus($line->total_line_tax);

                // Debit Inventory Account, Credit Stock Input Account for each line's subtotal.
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $inventoryAccount->id,
                    debit: $lineValue,
                    credit: Money::of(0, $currency->code),
                    description: "Inventory valuation for: {$line->description}",
                    partner_id: null,
                    analytic_account_id: null,
                );

                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $stockInputAccount->id,
                    debit: Money::of(0, $currency->code),
                    credit: $lineValue,
                    description: "Stock input for: {$line->description}",
                    partner_id: null,
                    analytic_account_id: null,
                );

                $totalValue = $totalValue->plus($lineValue);
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $currency->id,
                entry_date: $vendorBill->accounting_date,
                reference: 'INV/' . $vendorBill->bill_reference,
                description: 'Inventory Valuation for Bill ' . $vendorBill->bill_reference,
                source_type: VendorBill::class,
                source_id: $vendorBill->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
            );

            // This action creates and posts the entry immediately.
            return $this->createJournalEntryAction->execute($journalEntryDTO);
        });
    }
}
