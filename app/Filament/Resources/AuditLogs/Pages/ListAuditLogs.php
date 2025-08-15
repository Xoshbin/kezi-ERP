<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Actions;
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
