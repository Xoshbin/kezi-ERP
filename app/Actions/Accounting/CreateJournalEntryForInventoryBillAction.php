<?php

namespace App\Actions\Accounting;

use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateJournalEntryForInventoryBillAction
{
    public function __construct(protected JournalEntryService $journalEntryService)
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

            $journalLines = [];
            $totalValue = Money::of(0, $currency->code);

            foreach ($storableLines as $line) {
                $inventoryAccount = $line->product->inventoryAccount;
                $stockInputAccount = $line->product->stockInputAccount;

                if (!$inventoryAccount || !$stockInputAccount) {
                    throw new RuntimeException("Product ID {$line->product_id} is missing default inventory or stock input accounts.");
                }

                // Debit Inventory Account, Credit Stock Input Account for each line's subtotal.
                $journalLines[] = [
                    'account_id' => $inventoryAccount->id,
                    'debit' => $line->subtotal,
                    'credit' => Money::of(0, $currency->code),
                    'description' => "Inventory valuation for: {$line->description}",
                ];

                $journalLines[] = [
                    'account_id' => $stockInputAccount->id,
                    'debit' => Money::of(0, $currency->code),
                    'credit' => $line->subtotal,
                    'description' => "Stock input for: {$line->description}",
                ];

                $totalValue = $totalValue->plus($line->subtotal);
            }

            $journalEntryData = [
                'company_id' => $company->id,
                'journal_id' => $company->default_purchase_journal_id,
                'currency_id' => $currency->id,
                'entry_date' => $vendorBill->accounting_date,
                'reference' => 'INV/' . $vendorBill->bill_reference,
                'description' => 'Inventory Valuation for Bill ' . $vendorBill->bill_reference,
                'source_type' => VendorBill::class,
                'source_id' => $vendorBill->id,
                'created_by_user_id' => $user->id,
                'lines' => $journalLines,
            ];

            // This action creates and posts the entry immediately.
            return $this->journalEntryService->create($journalEntryData, true);
        });
    }
}
