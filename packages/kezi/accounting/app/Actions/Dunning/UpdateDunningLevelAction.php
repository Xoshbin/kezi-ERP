<?php

namespace Kezi\Accounting\Actions\Dunning;

use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\DunningLevelDTO;
use Kezi\Accounting\Models\DunningLevel;

class UpdateDunningLevelAction
{
    public function execute(DunningLevel $dunningLevel, DunningLevelDTO $dto): DunningLevel
    {
        return DB::transaction(function () use ($dunningLevel, $dto) {
            $dunningLevel->update([
                'company_id' => $dto->company_id,
                'name' => $dto->name,
                'days_overdue' => $dto->days_overdue,
                'email_subject' => $dto->email_subject,
                'email_body' => $dto->email_body,
                'print_letter' => $dto->print_letter,
                'send_email' => $dto->send_email,
                'charge_fee' => $dto->charge_fee,
                'fee_amount' => $dto->fee_amount,
                'fee_percentage' => $dto->fee_percentage,
                'fee_product_id' => $dto->fee_product_id,
            ]);

            return $dunningLevel->refresh();
        });
    }
}
