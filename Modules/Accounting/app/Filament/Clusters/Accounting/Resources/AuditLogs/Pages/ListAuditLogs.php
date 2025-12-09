<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
