<?php

namespace Modules\Payment\Actions\PettyCash;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Foundation\Services\SequenceService;
use Modules\Payment\DataTransferObjects\PettyCash\CreatePettyCashVoucherDTO;
use Modules\Payment\Enums\PettyCash\PettyCashVoucherStatus;
use Modules\Payment\Models\PettyCash\PettyCashVoucher;

class CreatePettyCashVoucherAction
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    ) {}

    public function execute(CreatePettyCashVoucherDTO $dto, User $user): PettyCashVoucher
    {
        return DB::transaction(function () use ($dto) {
            $company = \App\Models\Company::findOrFail($dto->company_id);

            $voucherNumber = $this->sequenceService->getNextNumber(
                $company,
                'petty_cash_voucher',
                'PCV'
            );

            return PettyCashVoucher::create([
                'company_id' => $dto->company_id,
                'fund_id' => $dto->fund_id,
                'voucher_number' => $voucherNumber,
                'expense_account_id' => $dto->expense_account_id,
                'partner_id' => $dto->partner_id,
                'amount' => $dto->amount,
                'voucher_date' => $dto->voucher_date,
                'description' => $dto->description,
                'receipt_reference' => $dto->receipt_reference,
                'status' => PettyCashVoucherStatus::Draft,
            ]);
        });
    }
}
