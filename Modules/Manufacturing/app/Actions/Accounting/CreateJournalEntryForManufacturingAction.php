<?php

namespace Modules\Manufacturing\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Models\JournalEntry;
use Modules\Manufacturing\Models\ManufacturingOrder;
use RuntimeException;

/**
 * Creates journal entry for completed manufacturing order
 *
 * Accounting Entry:
 * DR  Inventory - Finished Goods (actual cost of production)
 *     CR  Raw Materials Inventory (components consumed)
 *     CR  Manufacturing Overhead (labor/overhead if applicable)
 */
class CreateJournalEntryForManufacturingAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction,
    ) {}

    public function execute(ManufacturingOrder $mo, User $user): JournalEntry
    {
        return DB::transaction(function () use ($mo, $user) {
            // Load necessary relationships
            $mo->load('company', 'product', 'lines.product', 'destinationLocation', 'sourceLocation');

            $company = $mo->company;
            $currency = $company->currency;

            // Get required account IDs from company configuration
            $finishedGoodsAccountId = $company->default_finished_goods_inventory_id;
            $rawMaterialsAccountId = $company->default_raw_materials_inventory_id;
            $manufacturingJournalId = $company->default_manufacturing_journal_id;

            if (! $finishedGoodsAccountId || ! $rawMaterialsAccountId || ! $manufacturingJournalId) {
                throw new RuntimeException('Manufacturing accounts (Finished Goods, Raw Materials, Manufacturing Journal) are not configured for this company.');
            }

            $lineDTOs = [];
            $totalComponentCost = Money::of(0, $currency->code);

            // Credit Raw Materials for each component consumed
            foreach ($mo->lines as $line) {
                $lineCost = Money::ofMinor($line->unit_cost * 100, $currency->code)
                    ->multipliedBy($line->quantity_consumed);

                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $rawMaterialsAccountId,
                    debit: Money::of(0, $currency->code),
                    credit: $lineCost,
                    description: "Component: {$line->product->name} ({$line->quantity_consumed} units)",
                    partner_id: null,
                    analytic_account_id: null,
                );

                $totalComponentCost = $totalComponentCost->plus($lineCost);
            }

            // TODO: Add Manufacturing Overhead if work centers are used
            // For now, the total cost equals component cost
            $totalProductionCost = $totalComponentCost;

            // Debit Finished Goods Inventory
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $finishedGoodsAccountId,
                debit: $totalProductionCost,
                credit: Money::of(0, $currency->code),
                description: "Finished Goods: {$mo->product->name} ({$mo->quantity_produced} units)",
                partner_id: null,
                analytic_account_id: null,
            );

            // Create the journal entry
            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $manufacturingJournalId,
                currency_id: $currency->id,
                entry_date: now()->toDateString(),
                reference: $mo->number,
                description: "Manufacturing Order {$mo->number} - Production Complete",
                source_type: ManufacturingOrder::class,
                source_id: $mo->id,
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs,
                exchange_rate: null, // Using base currency
            );

            $journalEntry = $this->createJournalEntryAction->execute($journalEntryDTO);

            $mo->update(['journal_entry_id' => $journalEntry->id]);

            return $journalEntry;
        });
    }
}
