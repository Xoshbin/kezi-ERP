<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Actions\Loans\CalculateEIRAction;
use App\Actions\Loans\ComputeLoanScheduleAction;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use App\Models\LoanAgreement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanAgreement extends ViewRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('computeSchedule')
                ->label(__('Compute Schedule'))
                ->action(function () {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) { return; }
                    app(ComputeLoanScheduleAction::class)->execute($loan);
                    $loan->refresh();
                }),
            Actions\Action::make('recalculateEIR')
                ->label(__('Recalculate EIR'))
                ->action(function () {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) { return; }
                    app(CalculateEIRAction::class)->execute($loan);
                    $loan->refresh();
                }),
        ];
    }
}
