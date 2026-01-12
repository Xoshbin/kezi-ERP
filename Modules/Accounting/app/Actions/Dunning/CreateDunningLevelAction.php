<?php

namespace Modules\Accounting\Actions\Dunning;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\DunningLevelDTO;
use Modules\Accounting\Models\DunningLevel;

class CreateDunningLevelAction
{
    public function execute(DunningLevelDTO $dto): DunningLevel
    {
        return DB::transaction(function () use ($dto) {
            return DunningLevel::create([
                'company_id' => $dto->company_id,
                'name' => $dto->name,
                'days_overdue' => $dto->days_overdue,
                'email_subject' => $dto->email_subject,
                'email_body' => $dto->email_body,
                'print_letter' => $dto->print_letter,
                'send_email' => $dto->send_email,
            ]);
        });
    }
}
