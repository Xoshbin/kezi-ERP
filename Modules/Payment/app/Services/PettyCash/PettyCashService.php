<?php

namespace Modules\Payment\Services\PettyCash;

use App\Models\User;
use Modules\Payment\Actions\PettyCash\ClosePettyCashFundAction;
use Modules\Payment\Actions\PettyCash\CreatePettyCashFundAction;
use Modules\Payment\Actions\PettyCash\CreatePettyCashReplenishmentAction;
use Modules\Payment\Actions\PettyCash\CreatePettyCashVoucherAction;
use Modules\Payment\Actions\PettyCash\PostPettyCashVoucherAction;
use Modules\Payment\DataTransferObjects\PettyCash\CreatePettyCashFundDTO;
use Modules\Payment\DataTransferObjects\PettyCash\CreatePettyCashReplenishmentDTO;
use Modules\Payment\DataTransferObjects\PettyCash\CreatePettyCashVoucherDTO;
use Modules\Payment\Models\PettyCash\PettyCashFund;
use Modules\Payment\Models\PettyCash\PettyCashReplenishment;
use Modules\Payment\Models\PettyCash\PettyCashVoucher;

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
