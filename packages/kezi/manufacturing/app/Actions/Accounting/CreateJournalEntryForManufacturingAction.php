<?php

namespace Kezi\Manufacturing\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Manufacturing\Models\ManufacturingOrder;
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
            // Load necessary relationships (include company.currency for Money cast)
            $mo->load([
                'company.currency',
                'product',
                'lines.product',
                'lines.company.currency',
                'destinationLocation',
                'sourceLocation',
                'workOrders.workCenter',
            ]);

            $company = $mo->company;
            $currency = $company->currency;

            // Get required account IDs from company configuration
            $finishedGoodsAccountId = $company->default_finished_goods_inventory_id;
            $wipAccountId = $company->default_wip_account_id;
            $overheadAccountId = $company->default_manufacturing_overhead_account_id;
            $manufacturingJournalId = $company->default_manufacturing_journal_id;

            if (! $finishedGoodsAccountId || ! $wipAccountId || ! $manufacturingJournalId) {
                throw new RuntimeException('Manufacturing accounts (Finished Goods, WIP, Manufacturing Journal) are not configured for this company.');
            }

            $lineDTOs = [];
            $totalComponentCost = Money::of(0, $currency->code);

            // Calculate total cost from consumed components to credit WIP
            foreach ($mo->lines as $line) {
                // unit_cost is a Money object due to BaseCurrencyMoneyCast
                /** @var Money $unitCost */
                $unitCost = $line->unit_cost;
                $lineCost = $unitCost->multipliedBy($line->quantity_consumed);

                $totalComponentCost = $totalComponentCost->plus($lineCost);
            }

            // Calculate Manufacturing Overhead from work orders
            $totalOverheadCost = Money::of(0, $currency->code);
            foreach ($mo->workOrders as $workOrder) {
                if ($workOrder->actual_duration > 0) {
                    $hourlyCost = $workOrder->workCenter->hourly_cost;

                    $workOrderCost = $hourlyCost->multipliedBy($workOrder->actual_duration);
                    $totalOverheadCost = $totalOverheadCost->plus($workOrderCost);
                }
            }

            // Total cost equals component cost + overhead
            $totalProductionCost = $totalComponentCost->plus($totalOverheadCost);

            // Credit WIP Account (Transferring cost from WIP to Finished Goods)
            if (! $totalComponentCost->isZero()) {
                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $wipAccountId,
                    debit: Money::of(0, $currency->code),
                    credit: $totalComponentCost,
                    description: "WIP Clearance (Components): MO/{$mo->number}",
                    partner_id: null,
                    analytic_account_id: null,
                );
            }

            // Credit Overhead Account if applicable
            if (! $totalOverheadCost->isZero()) {
                if (! $overheadAccountId) {
                    throw new RuntimeException('Manufacturing Overhead account is not configured but overhead costs were calculated.');
                }

                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $overheadAccountId,
                    debit: Money::of(0, $currency->code),
                    credit: $totalOverheadCost,
                    description: "Manufacturing Overhead applied: MO/{$mo->number}",
                    partner_id: null,
                    analytic_account_id: null,
                );
            }

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
