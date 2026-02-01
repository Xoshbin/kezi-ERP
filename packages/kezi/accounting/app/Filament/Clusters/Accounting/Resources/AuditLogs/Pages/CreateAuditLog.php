<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs\AuditLogResource;

class CreateAuditLog extends CreateRecord
{
    protected static string $resource = AuditLogResource::class;
}
