<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use App\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
