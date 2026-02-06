<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashFundResource;

/**
 * @extends EditRecord<\Kezi\Payment\Models\PettyCash\PettyCashFund>
 */
class EditPettyCashFund extends EditRecord
{
    protected static string $resource = PettyCashFundResource::class;
}
