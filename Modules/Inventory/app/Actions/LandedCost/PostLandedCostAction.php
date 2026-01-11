<?php

namespace Modules\Inventory\Actions\LandedCost;

use Brick\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Inventory\Enums\Inventory\CostSource;
use Modules\Inventory\Enums\Inventory\LandedCostStatus;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Models\LandedCost;
use Modules\Inventory\Models\StockMoveValuation;

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

            // 1. Create Journal Entry for Landed Cost
            // We need to gather all affected products and their inventory accounts
            $inventoryAccountGroups = [];

            foreach ($landedCost->lines as $line) {
                $stockMove = $line->stockMove;
                $productLine = $stockMove->productLines->first();

                if (! $productLine || ! $productLine->product) {
                    continue;
                }

                $product = $productLine->product;
                $inventoryAccountId = $product->default_inventory_account_id;

                if (! $inventoryAccountId) {
                    throw new \Exception("Product {$product->id} ({$product->name}) does not have an inventory account configured");
                }

                // Group by inventory account for consolidated journal entry
                if (! isset($inventoryAccountGroups[$inventoryAccountId])) {
                    $inventoryAccountGroups[$inventoryAccountId] = [
                        'amount' => $zero,
                        'product_names' => [],
                    ];
                }

                $inventoryAccountGroups[$inventoryAccountId]['amount'] =
                    $inventoryAccountGroups[$inventoryAccountId]['amount']->plus($line->additional_cost);
                $inventoryAccountGroups[$inventoryAccountId]['product_names'][] = $product->name;
            }

            // Build Journal Entry Lines
            $journalEntryLines = [];

            // Debit lines: Inventory accounts (increases asset value)
            foreach ($inventoryAccountGroups as $accountId => $data) {
                $productList = implode(', ', array_unique($data['product_names']));

                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $accountId,
                    debit: $data['amount'],
                    credit: $zero,
                    description: "Landed cost allocation for: {$productList}",
                    partner_id: $landedCost->vendorBill?->partner_id,
                    analytic_account_id: null,
                );
            }

            // Credit line: Landed Cost Expense account
            // Use the company's default expense account or stock input account
            $expenseAccountId = $company->default_expense_account_id
                ?? $company->inventory_adjustment_account_id;

            if (! $expenseAccountId) {
                throw new \Exception("Company {$company->id} does not have a default expense account or inventory adjustment account configured for landed costs");
            }

            $journalEntryLines[] = new CreateJournalEntryLineDTO(
                account_id: $expenseAccountId,
                debit: $zero,
                credit: $landedCost->amount_total,
                description: "Landed cost expense: {$landedCost->description}",
                partner_id: $landedCost->vendorBill?->partner_id,
                analytic_account_id: null,
            );

            // Create Journal Entry DTO
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

            // Create the journal entry
            $journalEntry = app(CreateJournalEntryAction::class)->execute($journalEntryDTO);

            // Link journal entry to landed cost
            $landedCost->journal_entry_id = $journalEntry->id;

            // 2. Create Stock Move Valuations
            foreach ($landedCost->lines as $line) {
                $stockMove = $line->stockMove;
                $productLine = $stockMove->productLines->first();

                StockMoveValuation::create([
                    'company_id' => $landedCost->company_id,
                    'product_id' => $productLine->product_id,
                    'stock_move_id' => $stockMove->id,
                    'quantity' => 0, // No quantity change, only cost adjustment
                    'cost_impact' => $line->additional_cost,
                    'valuation_method' => $productLine->product->inventory_valuation_method ?? ValuationMethod::STANDARD,
                    'move_type' => $stockMove->move_type,
                    'journal_entry_id' => $journalEntry->id,
                    'source_type' => LandedCost::class,
                    'source_id' => $landedCost->id,
                    'cost_source' => CostSource::Manual,
                    'cost_source_reference' => $landedCost->id,
                ]);
            }

            // 3. Update Landed Cost status
            $landedCost->status = LandedCostStatus::Posted;
            $landedCost->save();
        });
    }
}
