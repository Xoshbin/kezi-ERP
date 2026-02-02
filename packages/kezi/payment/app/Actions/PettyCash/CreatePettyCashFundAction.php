<?php

namespace Kezi\Payment\Actions\PettyCash;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Payment\DataTransferObjects\PettyCash\CreatePettyCashFundDTO;
use Kezi\Payment\Enums\PettyCash\PettyCashFundStatus;
use Kezi\Payment\Models\PettyCash\PettyCashFund;

class CreatePettyCashFundAction
{
    public function execute(CreatePettyCashFundDTO $dto, User $user): PettyCashFund
    {
        return DB::transaction(function () use ($dto) {
            return PettyCashFund::create([
                'company_id' => $dto->company_id,
                'name' => $dto->name,
                'custodian_id' => $dto->custodian_id,
                'account_id' => $dto->account_id,
                'bank_account_id' => $dto->bank_account_id,
                'currency_id' => $dto->currency_id,
                'imprest_amount' => $dto->imprest_amount,
                'current_balance' => $dto->imprest_amount, // Initial balance = imprest amount
                'status' => PettyCashFundStatus::Active,
            ]);
        });
    }
}
