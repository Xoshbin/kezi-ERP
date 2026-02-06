<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashVoucherResource;

/**
 * @extends EditRecord<\Kezi\Payment\Models\PettyCash\PettyCashVoucher>
 */
class EditPettyCashVoucher extends EditRecord
{
    protected static string $resource = PettyCashVoucherResource::class;
}
