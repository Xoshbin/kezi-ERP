<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\DataTransferObjects\Assets\DisposeAssetDTO;
use App\Enums\Assets\AssetStatus;
use App\Models\Asset;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DisposeAssetAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(Asset $asset, DisposeAssetDTO $dto, User $user): Asset
    {
        return DB::transaction(function () use ($asset, $dto, $user) {
            $asset->load('company.currency', 'company.defaultBankJournal');
            $company = $asset->company;
            $currencyCode = $company->currency->code;

            // 1. Calculate all necessary financial values from the asset's history
            $purchaseValue = $asset->purchase_value;
            $disposalPrice = Money::of($dto->disposal_value, $currencyCode);

            $accumulatedDepreciation = Money::ofMinor(
                $asset->depreciationEntries()->where('status', 'posted')->sum('amount'),
                $currencyCode
            );

            $bookValue = $purchaseValue->minus($accumulatedDepreciation);
            $gainOrLoss = $disposalPrice->minus($bookValue);

            // 2. Build the journal entry lines based on standard disposal accounting rules
            $lines = [];
            $zero = Money::zero($currencyCode);

            // Debit Accumulated Depreciation to remove it from the books
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $asset->accumulated_depreciation_account_id,
                debit: $accumulatedDepreciation,
                credit: $zero,
                description: "Disposal of Asset: {$asset->name} (Accum. Dep.)",
                partner_id: null,
                analytic_account_id: null,
            );

            // Debit Cash/Bank for the proceeds from the sale
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $company->default_bank_account_id,
                debit: $disposalPrice,
                credit: $zero,
                description: "Disposal of Asset: {$asset->name} (Sale Proceeds)",
                partner_id: null,
                analytic_account_id: null,
            );

            // Credit the Asset account to remove the original cost
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $asset->asset_account_id,
                debit: $zero,
                credit: $purchaseValue,
                description: "Disposal of Asset: {$asset->name} (Original Cost)",
                partner_id: null,
                analytic_account_id: null,
            );

            // Record the Gain (Credit) or Loss (Debit) to balance the entry
            if ($gainOrLoss->isPositive()) { // It's a GAIN
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $dto->gain_loss_account_id,
                    debit: $zero,
                    credit: $gainOrLoss, // Gain is a credit (like revenue)
                    description: "Gain on Disposal of Asset: {$asset->name}",
                    partner_id: null,
                    analytic_account_id: null,
                );
            } elseif ($gainOrLoss->isNegative()) { // It's a LOSS
                $lines[] = new CreateJournalEntryLineDTO(
                    account_id: $dto->gain_loss_account_id,
                    debit: $gainOrLoss->abs(), // Loss is a debit (like an expense)
                    credit: $zero,
                    description: "Loss on Disposal of Asset: {$asset->name}",
                    partner_id: null,
                    analytic_account_id: null,
                );
            }

            // 3. Prepare the DTO to create the final journal entry
            if (! $company->default_bank_journal_id) {
                throw new RuntimeException('Default Bank Journal is not configured for the company.');
            }

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $company->default_bank_journal_id,
                currency_id: $company->currency_id,
                entry_date: $dto->disposal_date->toDateString(),
                reference: "DISPOSAL/{$asset->id}",
                description: "Disposal of Asset: {$asset->name}",
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lines,
                source_type: Asset::class,
                source_id: $asset->id
            );

            // 4. Delegate the creation to the dedicated Journal Entry Action
            $this->createJournalEntryAction->execute($journalEntryDTO);

            // 5. Update the asset's final status
            $asset->update([
                'status' => AssetStatus::Sold,
                'disposal_date' => $dto->disposal_date,
                'disposal_price' => $dto->disposal_value,
            ]);

            return $asset;
        });
    }
}
