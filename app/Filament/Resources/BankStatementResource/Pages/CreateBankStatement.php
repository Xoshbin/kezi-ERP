<?php

namespace App\Filament\Resources\BankStatementResource\Pages;

use App\Filament\Resources\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBankStatement extends CreateRecord
{
    protected static string $resource = BankStatementResource::class;
}
