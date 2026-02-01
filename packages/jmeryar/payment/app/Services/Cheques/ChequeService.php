<?php

namespace Jmeryar\Payment\Services\Cheques;

use App\Models\User;
use Jmeryar\Payment\Actions\Cheques\CancelChequeAction;
use Jmeryar\Payment\Actions\Cheques\ClearChequeAction;
use Jmeryar\Payment\Actions\Cheques\DepositChequeAction;
use Jmeryar\Payment\Actions\Cheques\HandOverChequeAction;
use Jmeryar\Payment\Actions\Cheques\IssueChequeAction;
use Jmeryar\Payment\Actions\Cheques\ReceiveChequeAction;
use Jmeryar\Payment\Actions\Cheques\RegisterBounceAction;
use Jmeryar\Payment\DataTransferObjects\Cheques\BounceChequeDTO;
use Jmeryar\Payment\DataTransferObjects\Cheques\ClearChequeDTO;
use Jmeryar\Payment\DataTransferObjects\Cheques\CreateChequeDTO;
use Jmeryar\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Jmeryar\Payment\Models\Cheque;

class ChequeService
{
    public function __construct(
        protected IssueChequeAction $issueChequeAction,
        protected ReceiveChequeAction $receiveChequeAction,
        protected HandOverChequeAction $handOverChequeAction,
        protected DepositChequeAction $depositChequeAction,
        protected ClearChequeAction $clearChequeAction,
        protected RegisterBounceAction $registerBounceAction,
        protected CancelChequeAction $cancelChequeAction,
    ) {}

    public function issue(CreateChequeDTO $dto, User $user): Cheque
    {
        return $this->issueChequeAction->execute($dto, $user);
    }

    public function receive(CreateChequeDTO $dto, User $user): Cheque
    {
        return $this->receiveChequeAction->execute($dto, $user);
    }

    public function handOver(Cheque $cheque, User $user): void
    {
        $this->handOverChequeAction->execute($cheque, $user);
    }

    public function deposit(Cheque $cheque, DepositChequeDTO $dto, User $user): void
    {
        $this->depositChequeAction->execute($cheque, $dto, $user);
    }

    public function clear(Cheque $cheque, ClearChequeDTO $dto, User $user): void
    {
        $this->clearChequeAction->execute($cheque, $dto, $user);
    }

    public function bounce(Cheque $cheque, BounceChequeDTO $dto, User $user): void
    {
        $this->registerBounceAction->execute($cheque, $dto, $user);
    }

    public function cancel(Cheque $cheque, User $user): void
    {
        $this->cancelChequeAction->execute($cheque, $user);
    }
}
