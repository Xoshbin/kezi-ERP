<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
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
            $vendorBill->load('company.currency', 'currency', 'lines.product.inventoryAccount', 'lines.product.stockInputAccount');

            $company = $vendorBill->company;
            $baseCurrency = $company->currency;
            $foreignCurrency = $vendorBill->currency;

            // Determine the exchange rate. If it's the same currency, the rate is 1.
            $exchangeRate = ($baseCurrency->id === $foreignCurrency->id) ? 1.0 : $foreignCurrency->exchange_rate;

            $storableLines = $vendorBill->lines->where('product.type', 'storable');

            if ($storableLines->isEmpty()) {
                throw new RuntimeException('This action should only be called for bills with storable items.');
            }

            $lineDTOs = [];
            $zeroAmountInBase = Money::of(0, $baseCurrency->code);
            $totalValue = Money::zero($baseCurrency->code);

            foreach ($storableLines as $line) {
                $inventoryAccount = $line->product->inventoryAccount;
                $stockInputAccount = $line->product->stockInputAccount;

                if (!$inventoryAccount || !$stockInputAccount) {
                    throw new RuntimeException("Product ID {$line->product_id} is missing default inventory or stock input accounts.");
                }

                $lineValue = $line->subtotal->plus($line->total_line_tax);

                // Convert line value to base currency
                $lineValueInBase = Money::of($lineValue->getAmount(), $baseCurrency->code)->multipliedBy($exchangeRate, RoundingMode::HALF_UP);

                // Debit Inventory Account, Credit Stock Input Account for each line's subtotal.
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $inventoryAccount->id,
                    debit: $lineValueInBase,
                    credit: $zeroAmountInBase,
                    description: "Inventory valuation for: {$line->description}",
                    partner_id: null,
                    analytic_account_id: null,
                    original_currency_amount: $lineValue, // Original Money object
                    original_currency_id: $vendorBill->currency_id, // Original currency ID
                    exchange_rate_at_transaction: $exchangeRate,
                );

                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $stockInputAccount->id,
                    debit: $zeroAmountInBase,
                    credit: $lineValueInBase,
                    description: "Stock input for: {$line->description}",
                    partner_id: null,
                    analytic_account_id: null,
                    original_currency_amount: $lineValue, // Original Money object
                    original_currency_id: $vendorBill->currency_id, // Original currency ID
                    exchange_rate_at_transaction: $exchangeRate,
                );

                $totalValue = $totalValue->plus($lineValueInBase);
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $baseCurrency->id, // Journal entry is always in company base currency
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
