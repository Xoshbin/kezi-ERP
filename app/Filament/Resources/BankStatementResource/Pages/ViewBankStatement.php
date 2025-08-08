<?php

namespace App\Filament\Resources\BankStatementResource\Pages;

use App\Filament\Resources\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBankStatement extends ViewRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('reconcile')
                ->label(__('bank_statement.reconcile'))
                ->icon('heroicon-o-scale')
                ->color('success')
                ->url(fn(): string => static::getResource()::getUrl('reconcile', ['record' => $this->record])),
        ];
    }
}
