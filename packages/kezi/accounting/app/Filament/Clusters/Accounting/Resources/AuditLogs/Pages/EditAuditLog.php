<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;

/**
 * @extends EditRecord<\Kezi\Foundation\Models\AuditLog>
 */
class EditAuditLog extends EditRecord
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
