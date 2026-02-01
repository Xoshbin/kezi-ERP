<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;

class CreateAuditLog extends CreateRecord
{
    protected static string $resource = AuditLogResource::class;
}
