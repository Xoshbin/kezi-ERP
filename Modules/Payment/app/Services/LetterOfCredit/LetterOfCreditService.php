<?php

namespace Modules\Payment\Services\LetterOfCredit;

use App\Models\User;
use Modules\Payment\Actions\LetterOfCredit\CancelLetterOfCreditAction;
use Modules\Payment\Actions\LetterOfCredit\CreateLCChargeAction;
use Modules\Payment\Actions\LetterOfCredit\CreateLetterOfCreditAction;
use Modules\Payment\Actions\LetterOfCredit\IssueLetterOfCreditAction;
use Modules\Payment\Actions\LetterOfCredit\UtilizeLetterOfCreditAction;
use Modules\Payment\DataTransferObjects\LetterOfCredit\CreateLCChargeDTO;
use Modules\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Modules\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO;
use Modules\Payment\DataTransferObjects\LetterOfCredit\UtilizeLCDTO;
use Modules\Payment\Models\LCCharge;
use Modules\Payment\Models\LCUtilization;
use Modules\Payment\Models\LetterOfCredit;

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
