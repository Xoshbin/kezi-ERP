<?php

namespace Kezi\Payment\Services\LetterOfCredit;

use App\Models\User;
use Kezi\Payment\Actions\LetterOfCredit\CancelLetterOfCreditAction;
use Kezi\Payment\Actions\LetterOfCredit\CreateLCChargeAction;
use Kezi\Payment\Actions\LetterOfCredit\CreateLetterOfCreditAction;
use Kezi\Payment\Actions\LetterOfCredit\IssueLetterOfCreditAction;
use Kezi\Payment\Actions\LetterOfCredit\UtilizeLetterOfCreditAction;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\CreateLCChargeDTO;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Kezi\Payment\Models\LCCharge;
use Kezi\Payment\Models\LCUtilization;
use Kezi\Payment\Models\LetterOfCredit;

class LetterOfCreditService
{
    public function __construct(
        protected CreateLetterOfCreditAction $createAction,
        protected IssueLetterOfCreditAction $issueAction,
        protected UtilizeLetterOfCreditAction $utilizeAction,
        protected CreateLCChargeAction $createChargeAction,
        protected CancelLetterOfCreditAction $cancelAction,
    ) {}

    public function create(CreateLetterOfCreditDTO $dto, User $user): LetterOfCredit
    {
        return $this->createAction->execute($dto, $user);
    }

    public function issue(LetterOfCredit $lc, IssueLetterOfCreditDTO $dto, User $user): void
    {
        $this->issueAction->execute($lc, $dto, $user);
    }

    public function utilize(LetterOfCredit $lc, UtilizeLCDTO $dto, User $user): LCUtilization
    {
        return $this->utilizeAction->execute($lc, $dto, $user);
    }

    public function recordCharge(CreateLCChargeDTO $dto, User $user): LCCharge
    {
        return $this->createChargeAction->execute($dto, $user);
    }

    public function cancel(LetterOfCredit $lc, User $user): void
    {
        $this->cancelAction->execute($lc, $user);
    }
}
