<?php

namespace App\Filament\Resources\BankStatements\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\BankStatements\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBankStatement extends ViewRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('reconcile')
                ->label(__('bank_statement.reconcile'))
                ->icon('heroicon-o-scale')
                ->color('success')
                ->url(fn(): string => static::getResource()::getUrl('reconcile', ['record' => $this->record])),
        ];
    }
}
