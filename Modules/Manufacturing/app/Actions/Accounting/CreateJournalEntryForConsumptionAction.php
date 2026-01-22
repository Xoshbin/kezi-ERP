<?php

namespace Modules\Manufacturing\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Models\StockMove;
use Modules\Manufacturing\Models\ManufacturingOrder;
use RuntimeException;

/**
 * Creates journal entry for component consumption in manufacturing
 *
 * Accounting Entry:
 * DR  WIP Account (Work in Progress)
 *     CR  Raw Materials Inventory
 */
class CreateJournalEntryForConsumptionAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    public function execute(ManufacturingOrder $mo, StockMove $stockMove, User $user): JournalEntry
    {
        return DB::transaction(function () use ($mo, $stockMove, $user) {
            $mo->load('company.currency', 'lines.product');
            $stockMove->load('productLines.product');

            $company = $mo->company;
            $currency = $company->currency;

            // Get required account IDs from company configuration
            $rawMaterialsAccountId = $company->default_raw_materials_inventory_id;
            $wipAccountId = $company->default_wip_account_id;
            $manufacturingJournalId = $company->default_manufacturing_journal_id;

            if (! $rawMaterialsAccountId || ! $wipAccountId || ! $manufacturingJournalId) {
                throw new RuntimeException('Manufacturing accounts (Raw Materials, WIP, Manufacturing Journal) are not configured for this company.');
            }

            $lineDTOs = [];
            $totalCost = Money::of(0, $currency->code);

            // Iterate over stock move lines to determine what was consumed
            foreach ($stockMove->productLines as $moveLine) {
                // Find corresponding MO line to get the planned unit cost
                // In a real scenario, we might use the actual cost of the stock being moved (FIFO/AVCO),
                // but here we stick to the MO line unit cost as per current logic.
                $moLine = $mo->lines->where('product_id', $moveLine->product_id)->first();

                if (! $moLine) {
                    continue; // Should not happen if data is consistent
                }

                /** @var Money $unitCost */
                $unitCost = $moLine->unit_cost;
                $lineCost = $unitCost->multipliedBy($moveLine->quantity);

                // Credit Raw Materials
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $rawMaterialsAccountId,
                    debit: Money::of(0, $currency->code),
                    credit: $lineCost,
                    description: "Component Consumption: {$moveLine->product->name} ({$moveLine->quantity} units)",
                    partner_id: null,
                    analytic_account_id: null,
                );

                $totalCost = $totalCost->plus($lineCost);
            }

            // Debit WIP Account
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $wipAccountId,
                debit: $totalCost,
                credit: Money::of(0, $currency->code),
                description: "WIP Addition: MO/{$mo->number}",
                partner_id: null,
                analytic_account_id: null,
            );

            // Create the journal entry
            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $manufacturingJournalId,
                currency_id: $currency->id,
                entry_date: now()->toDateString(),
                reference: $stockMove->reference ?? "MO/{$mo->number}",
                description: "Manufacturing Order {$mo->number} - Component Consumption",
                source_type: ManufacturingOrder::class,
                source_id: $mo->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                exchange_rate: null, // Using base currency
            );

            $journalEntry = $this->createJournalEntryAction->execute($journalEntryDTO);

            return $journalEntry;
        });
    }
}
