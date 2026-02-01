<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;

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
