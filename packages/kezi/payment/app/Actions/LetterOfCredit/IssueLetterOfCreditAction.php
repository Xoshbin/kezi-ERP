<?php

namespace Kezi\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Models\LetterOfCredit;

class IssueLetterOfCreditAction
{
    public function execute(LetterOfCredit $lc, IssueLetterOfCreditDTO $dto, User $user): void
    {
        DB::transaction(function () use ($lc, $dto) {
            // Only draft LCs can be issued
            if ($lc->status !== LCStatus::Draft) {
                throw new \RuntimeException('Only draft LCs can be issued');
            }

            $lc->update([
                'bank_reference' => $dto->bank_reference,
                'issue_date' => $dto->issue_date,
                'status' => LCStatus::Issued,
            ]);
        });
    }
}
