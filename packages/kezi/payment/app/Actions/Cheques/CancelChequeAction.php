<?php

namespace Kezi\Payment\Actions\Cheques;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Models\Cheque;

class CancelChequeAction
{
    public function execute(Cheque $cheque, User $user): void
    {
        if ($cheque->status !== ChequeStatus::Draft) {
            throw new \DomainException(__('payment::exceptions.cheque.draft_only_for_cancel'));
        }

        DB::transaction(function () use ($cheque) {
            $cheque->update([
                'status' => ChequeStatus::Cancelled,
            ]);
        });
    }
}
