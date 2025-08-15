<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
