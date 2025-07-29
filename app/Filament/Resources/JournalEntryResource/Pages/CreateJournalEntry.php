<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use App\Models\Company;
use App\Models\LockDate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;
use Brick\Money\Money;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $entryDate = \Carbon\Carbon::parse($data['entry_date']);
        $lockDate = LockDate::where('company_id', $data['company_id'])->first();
        if ($lockDate && $entryDate->lte($lockDate->locked_until)) {
            throw ValidationException::withMessages([
                'data.entry_date' => 'The accounting period is locked and cannot be modified.',
            ]);
        }
        
        // The 'lines' data is now reliably in the $data array.
        $lines = $data['lines'] ?? [];

        // Perform balance validation
        $totalDebit = collect($lines)->sum(fn($line) => $line['debit'] ?? 0);
        $totalCredit = collect($lines)->sum(fn($line) => $line['credit'] ?? 0);

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'data.lines' => 'The total debits must equal the total credits.',
            ]);
        }

        $data['total_debit'] = $totalDebit;
        $data['total_credit'] = $totalCredit;

        if (empty($data['currency_id']) && !empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            if ($company) {
                $data['currency_id'] = $company->currency_id;
            }
        }
        
        // We no longer unset the lines here. They are passed to the next step.
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Separate lines data from parent data.
            $linesData = $data['lines'] ?? [];
            unset($data['lines']);

            // 2. Create the parent JournalEntry.
            $journalEntry = static::getModel()::create($data);

            // 3. Manually prepare and create the lines.
            if (!empty($linesData)) {
                $currencyId = $data['currency_id'];
                
                // --- START OF THE FIX ---
                // Fetch the currency model to get its string code (e.g., 'IQD')
                $currency = Currency::find($currencyId);
                if (!$currency) {
                    throw new \Exception("Could not find Currency with ID: {$currencyId}");
                }
                $currencyCode = $currency->code;

                $linesWithMoneyObjects = array_map(function ($line) use ($currencyId, $currencyCode) {
                    // Pre-convert the numeric strings into complete Money objects.
                    $line['debit'] = Money::of($line['debit'] ?? 0, $currencyCode);
                    $line['credit'] = Money::of($line['credit'] ?? 0, $currencyCode);
                    
                    // Also pass the currency_id for the database column.
                    $line['currency_id'] = $currencyId; 
                    return $line;
                }, $linesData);

                // Eloquent will now receive Money objects and the cast will work perfectly.
                $journalEntry->lines()->createMany($linesWithMoneyObjects);
                // --- END OF THE FIX ---
            }

            return $journalEntry;
        });
    }
}