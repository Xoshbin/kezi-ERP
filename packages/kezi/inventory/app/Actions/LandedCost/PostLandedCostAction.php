<?php

namespace Kezi\Inventory\Actions\LandedCost;

use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Kezi\Inventory\Enums\Inventory\CostSource;
use Kezi\Inventory\Enums\Inventory\LandedCostStatus;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\LandedCost;
use Kezi\Inventory\Models\StockMoveValuation;

class PostLandedCostAction
{
    public function execute(LandedCost $landedCost): void
    {
        if ($landedCost->status === LandedCostStatus::Posted) {
            return;
        }

        DB::transaction(function () use ($landedCost) {
            $company = $landedCost->company;
            $currencyCode = $company->currency->code;
            $zero = Money::of(0, $currencyCode);

            // 1. Prepare Journal Entry Data (Grouping by accounts)
            $journalDebits = []; // Account ID => Money amount
            $descriptions = []; // Account ID => Array of descriptions

            // Helper to add debit to an account
            $addDebit = function ($accountId, Money $amount, $desc) use (&$journalDebits, &$descriptions, $zero) {
                if (! isset($journalDebits[$accountId])) {
                    $journalDebits[$accountId] = $zero;
                    $descriptions[$accountId] = [];
                }
                $journalDebits[$accountId] = $journalDebits[$accountId]->plus($amount);
                $descriptions[$accountId][] = $desc;
            };

            // 2. Process each Landed Cost Line
            foreach ($landedCost->lines as $line) {
                $stockMove = $line->stockMove;
                if (! $stockMove) {
                    continue;
                }

                $productLine = $stockMove->productLines->first();
                $product = $productLine?->product;

                if (! $product) {
                    continue;
                }

                $additionalCost = $line->additional_cost;
                $totalQty = (float) $productLine->quantity;

                if ($totalQty <= 0) {
                    continue; // Should not happen for valid moves
                }

                // Determine Accounts
                $inventoryAccountId = $product->default_inventory_account_id;
                $cogsAccountId = $product->default_cogs_account_id;

                if (! $inventoryAccountId) {
                    throw new \Exception("Product {$product->id} ({$product->name}) does not have an inventory account configured");
                }
                if (! $cogsAccountId) {
                    throw new \Exception("Product {$product->id} ({$product->name}) does not have a COGS account configured");
                }

                /** @var ValuationMethod $valuationMethod */
                $valuationMethod = $product->inventory_valuation_method;

                // Logic for FIFO/LIFO: Update Cost Layers & Split Cost
                if (in_array($valuationMethod, [ValuationMethod::FIFO, ValuationMethod::LIFO])) {
                    // Find related cost layers for this specific stock move
                    /** @var \Illuminate\Database\Eloquent\Collection<int, \Kezi\Inventory\Models\InventoryCostLayer> $layers */
                    $layers = \Kezi\Inventory\Models\InventoryCostLayer::query()
                        ->where('source_type', \Kezi\Inventory\Models\StockMove::class)
                        ->where('source_id', $stockMove->id)
                        ->where('product_id', $product->id)
                        ->get();

                    $remainingQtyInLayers = (float) $layers->sum('remaining_quantity');
                    $soldQty = (float) ($totalQty - $remainingQtyInLayers);

                    // Calculate Split using integer ratios for Brick\Money
                    /** @var Money[] $split */
                    $split = $additionalCost->allocate(
                        (int) (round($remainingQtyInLayers, 4) * 10000),
                        (int) (round(max(0.0, $soldQty), 4) * 10000)
                    );
                    $inventoryPortion = $split[0];
                    $cogsPortion = $split[1];

                    // Update Layers Unit Cost
                    if ($remainingQtyInLayers > 0) {
                        foreach ($layers as $layer) {
                            if ($layer->remaining_quantity > 0) {
                                // Calculate share of landed cost for this layer
                                /** @var Money[] $layerAllocation */
                                $layerAllocation = $inventoryPortion->allocate(
                                    (int) (round($layer->remaining_quantity, 4) * 10000),
                                    (int) (round(max(0.0, $remainingQtyInLayers - $layer->remaining_quantity), 4) * 10000)
                                );
                                $layerShare = $layerAllocation[0];

                                $layerQty = (float) $layer->remaining_quantity;
                                $costPerUnitIncrease = $layerShare->dividedBy($layerQty, \Brick\Math\RoundingMode::HALF_UP);

                                /** @var Money $currentCost */
                                $currentCost = $layer->cost_per_unit;
                                $layer->cost_per_unit = $currentCost->plus($costPerUnitIncrease);
                                $layer->save();
                            }
                        }
                    }

                    // Add to Debits
                    if ($inventoryPortion->isPositive()) {
                        $addDebit($inventoryAccountId, $inventoryPortion, "Landed Cost (Stock): {$product->name}");
                    }
                    if ($cogsPortion->isPositive()) {
                        $addDebit($cogsAccountId, $cogsPortion, "Landed Cost (Sold): {$product->name}");
                    }

                } else {
                    // Fallback to original behavior for AVCO/Standard: Everything to Asset
                    $addDebit($inventoryAccountId, $additionalCost, "Landed Cost: {$product->name}");
                }

                // Record Valuation Move for history
                StockMoveValuation::create([
                    'company_id' => $landedCost->company_id,
                    'product_id' => $product->id,
                    'stock_move_id' => $stockMove->id,
                    'quantity' => 0,
                    'cost_impact' => $additionalCost,
                    'valuation_method' => $valuationMethod,
                    'move_type' => $stockMove->move_type,
                    'source_type' => LandedCost::class,
                    'source_id' => $landedCost->id,
                    'cost_source' => CostSource::Manual,
                    'cost_source_reference' => (string) $landedCost->id,
                ]);
            }

            // 3. Create Journal Entry Lines
            $journalEntryLines = [];
            foreach ($journalDebits as $accountId => $amount) {
                if ($amount->isPositive()) {
                    $descStr = implode(', ', array_unique($descriptions[$accountId]));
                    $journalEntryLines[] = new CreateJournalEntryLineDTO(
                        account_id: $accountId,
                        debit: $amount,
                        credit: $zero,
                        description: 'Landed Cost Allocation: '.substr($descStr, 0, 200),
                        partner_id: $landedCost->vendorBill?->vendor_id,
                        analytic_account_id: null,
                    );
                }
            }

            // Credit line: Expense Account
            $expenseAccountId = $company->default_expense_account_id
                ?? $company->inventory_adjustment_account_id;

            if (! $expenseAccountId) {
                throw new \Exception("Company {$company->id} does not have a default expense account or inventory adjustment account configured for landed costs");
            }

            $journalEntryLines[] = new CreateJournalEntryLineDTO(
                account_id: $expenseAccountId,
                debit: $zero,
                credit: $landedCost->amount_total,
                description: "Landed Cost Expense: {$landedCost->description}",
                partner_id: $landedCost->vendorBill?->vendor_id,
                analytic_account_id: null,
            );

            // 4. Create and Post Journal Entry
            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_purchase_journal_id,
                currency_id: $company->currency_id,
                entry_date: $landedCost->date->toDateString(),
                reference: "LC-{$landedCost->id}",
                description: "Landed Cost: {$landedCost->description}",
                created_by_user_id: (int) (Auth::id() ?? $landedCost->created_by_user_id ?? 1),
                is_posted: true,
                lines: $journalEntryLines,
                source_type: LandedCost::class,
                source_id: $landedCost->id,
            );

            $journalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDTO);

            // 5. Finalize
            $landedCost->journal_entry_id = $journalEntry->id;
            $landedCost->status = LandedCostStatus::Posted;
            $landedCost->save();

            // Link Valuations to JE (Optimistic update or recreate)
            // Since we created them above without JE ID, let's update them
            StockMoveValuation::where('source_type', LandedCost::class)
                ->where('source_id', $landedCost->id)
                ->update(['journal_entry_id' => $journalEntry->id]);

        });
    }
}
