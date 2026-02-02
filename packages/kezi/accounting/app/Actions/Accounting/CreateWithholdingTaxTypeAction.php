<?php

namespace Kezi\Accounting\Actions\Accounting;

use Illuminate\Support\Facades\DB;
use Kezi\Accounting\DataTransferObjects\Accounting\CreateWithholdingTaxTypeDTO;
use Kezi\Accounting\Models\WithholdingTaxType;

class CreateWithholdingTaxTypeAction
{
    public function execute(CreateWithholdingTaxTypeDTO $dto): WithholdingTaxType
    {
        return DB::transaction(function () use ($dto) {
            return WithholdingTaxType::create([
                'company_id' => $dto->company_id,
                'name' => $dto->name,
                'rate' => $dto->rate,
                'threshold_amount' => $dto->threshold_amount,
                'applicable_to' => $dto->applicable_to,
                'withholding_account_id' => $dto->withholding_account_id,
                'is_active' => $dto->is_active,
            ]);
        });
    }
}
