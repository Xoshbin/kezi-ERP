<?php

namespace Jmeryar\Payment\Services\PettyCash;

use App\Models\User;
use Jmeryar\Payment\Actions\PettyCash\ClosePettyCashFundAction;
use Jmeryar\Payment\Actions\PettyCash\CreatePettyCashFundAction;
use Jmeryar\Payment\Actions\PettyCash\CreatePettyCashReplenishmentAction;
use Jmeryar\Payment\Actions\PettyCash\CreatePettyCashVoucherAction;
use Jmeryar\Payment\Actions\PettyCash\PostPettyCashVoucherAction;
use Jmeryar\Payment\DataTransferObjects\PettyCash\CreatePettyCashFundDTO;
use Jmeryar\Payment\DataTransferObjects\PettyCash\CreatePettyCashReplenishmentDTO;
use Jmeryar\Payment\DataTransferObjects\PettyCash\CreatePettyCashVoucherDTO;
use Jmeryar\Payment\Models\PettyCash\PettyCashFund;
use Jmeryar\Payment\Models\PettyCash\PettyCashReplenishment;
use Jmeryar\Payment\Models\PettyCash\PettyCashVoucher;

class PettyCashService
{
    public function __construct(
        protected CreatePettyCashFundAction $createFundAction,
        protected ClosePettyCashFundAction $closeFundAction,
        protected CreatePettyCashVoucherAction $createVoucherAction,
        protected PostPettyCashVoucherAction $postVoucherAction,
        protected CreatePettyCashReplenishmentAction $createReplenishmentAction,
    ) {}

    public function createFund(CreatePettyCashFundDTO $dto, User $user): PettyCashFund
    {
        return $this->createFundAction->execute($dto, $user);
    }

    public function closeFund(PettyCashFund $fund, User $user): void
    {
        $this->closeFundAction->execute($fund, $user);
    }

    public function recordExpense(CreatePettyCashVoucherDTO $dto, User $user): PettyCashVoucher
    {
        return $this->createVoucherAction->execute($dto, $user);
    }

    public function postVoucher(PettyCashVoucher $voucher, User $user): void
    {
        $this->postVoucherAction->execute($voucher, $user);
    }

    public function replenish(CreatePettyCashReplenishmentDTO $dto, User $user): PettyCashReplenishment
    {
        return $this->createReplenishmentAction->execute($dto, $user);
    }

    public function getFundBalance(PettyCashFund $fund): \Brick\Money\Money
    {
        return $fund->fresh()->current_balance;
    }
}
