<?php

namespace Jmeryar\Payment\Actions\Cheques;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Payment\Enums\Cheques\ChequeStatus;
use Jmeryar\Payment\Models\Cheque;

class CancelChequeAction
{
    public function execute(Cheque $cheque, User $user): void
    {
        if ($cheque->status !== ChequeStatus::Draft) {
            throw new \DomainException('Only draft cheques can be cancelled. Use Void or Bounce for processed cheques.');
        }

        DB::transaction(function () use ($cheque) {
            $cheque->update([
                'status' => ChequeStatus::Cancelled,
            ]);
        });
    }
}
