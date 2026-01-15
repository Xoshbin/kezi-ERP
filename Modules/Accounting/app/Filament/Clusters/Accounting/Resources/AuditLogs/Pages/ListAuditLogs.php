<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;
use Modules\Foundation\Filament\Actions\DocsAction;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-audit-logs'),
            CreateAction::make(),
        ];
    }
}
