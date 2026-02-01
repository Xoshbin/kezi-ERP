<?php

namespace Jmeryar\Payment\Services\LetterOfCredit;

use App\Models\User;
use Jmeryar\Payment\Actions\LetterOfCredit\CancelLetterOfCreditAction;
use Jmeryar\Payment\Actions\LetterOfCredit\CreateLCChargeAction;
use Jmeryar\Payment\Actions\LetterOfCredit\CreateLetterOfCreditAction;
use Jmeryar\Payment\Actions\LetterOfCredit\IssueLetterOfCreditAction;
use Jmeryar\Payment\Actions\LetterOfCredit\UtilizeLetterOfCreditAction;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\CreateLCChargeDTO;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Jmeryar\Payment\Models\LCCharge;
use Jmeryar\Payment\Models\LCUtilization;
use Jmeryar\Payment\Models\LetterOfCredit;

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
