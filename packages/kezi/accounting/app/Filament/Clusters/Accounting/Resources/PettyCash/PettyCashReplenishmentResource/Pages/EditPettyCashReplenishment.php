<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\PettyCash\PettyCashReplenishmentResource;

/**
 * @extends EditRecord<\Kezi\Payment\Models\PettyCash\PettyCashReplenishment>
 */
class EditPettyCashReplenishment extends EditRecord
{
    protected static string $resource = PettyCashReplenishmentResource::class;
}
