<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Modules\Accounting\Actions\Accounting\CloseFiscalYearAction;
use Modules\Accounting\Actions\Accounting\ReopenFiscalYearAction;
use Modules\Accounting\DataTransferObjects\Accounting\CloseFiscalYearDTO;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Exceptions\FiscalYearCannotBeReopenedException;
use Modules\Accounting\Exceptions\FiscalYearNotReadyToCloseException;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\FiscalYearResource;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Services\FiscalYearService;

class EditFiscalYear extends EditRecord
{
    protected static string $resource = FiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCloseFiscalYearAction(),
            $this->getReopenFiscalYearAction(),
        ];
    }

    /**
     * Get the Close Fiscal Year action with wizard.
     */
    protected function getCloseFiscalYearAction(): Action
    {
        return Action::make('closeFiscalYear')
            ->label(__('accounting::fiscal_year.action_close'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->visible(fn (): bool => $this->getRecord()->state === FiscalYearState::Open)
            ->steps([
                // Step 1: Preview P&L summary

                Step::make(__('accounting::fiscal_year.wizard_step_preview'))
                    ->description(__('accounting::fiscal_year.wizard_step_preview_desc'))
                    ->schema(function () {
                        /** @var FiscalYear $fiscalYear */
                        $fiscalYear = $this->getRecord();
                        $service = app(FiscalYearService::class);
                        $balances = $service->getProfitAndLossBalances($fiscalYear);
                        $validation = $service->validateReadyToClose($fiscalYear);

                        $components = [];

                        if (! $validation['ready']) {
                            $components[] = Section::make(__('accounting::fiscal_year.validation_failed'))
                                ->schema([
                                    Placeholder::make('validation_errors')
                                        ->hiddenLabel()
                                        ->content(implode("\n", $validation['issues']))
                                        ->extraAttributes(['class' => 'text-danger-600']),
                                ]);
                        }

                        $components[] = Section::make(__('accounting::fiscal_year.pl_summary'))
                            ->schema([
                                Placeholder::make('total_income')
                                    ->label(__('accounting::fiscal_year.total_income'))
                                    ->content($balances['income']->formatTo(app()->getLocale())),

                                Placeholder::make('total_expenses')
                                    ->label(__('accounting::fiscal_year.total_expenses'))
                                    ->content($balances['expenses']->formatTo(app()->getLocale())),

                                Placeholder::make('net_income')
                                    ->label(__('accounting::fiscal_year.net_income'))
                                    ->content($balances['netIncome']->formatTo(app()->getLocale()))
                                    ->extraAttributes(['class' => 'font-bold']),
                            ]);

                        return $components;
                    }),

                // Step 2: Select Retained Earnings account
                Step::make(__('accounting::fiscal_year.wizard_step_config'))
                    ->description(__('accounting::fiscal_year.wizard_step_config_desc'))
                    ->schema(function () {
                        $service = app(FiscalYearService::class);
                        $company = filament()->getTenant();
                        $suggestedAccount = $service->getRetainedEarningsAccount($company);

                        return [
                            Select::make('retained_earnings_account_id')
                                ->label(__('accounting::fiscal_year.field_retained_earnings_account'))
                                ->options(
                                    $service->getEquityAccounts($company)
                                        ->mapWithKeys(fn ($account) => [$account->id => $account->code.' - '.$account->name])
                                )
                                ->default($suggestedAccount?->id)
                                ->required()
                                ->searchable()
                                ->helperText(__('accounting::fiscal_year.field_retained_earnings_account_help')),

                            Textarea::make('description')
                                ->label(__('accounting::fiscal_year.field_closing_description'))
                                ->rows(2),
                        ];
                    }),
            ])
            ->action(function (array $data) {
                try {
                    /** @var FiscalYear $fiscalYear */
                    $fiscalYear = $this->getRecord();

                    $dto = new CloseFiscalYearDTO(
                        fiscalYear: $fiscalYear,
                        retainedEarningsAccountId: $data['retained_earnings_account_id'],
                        closedByUserId: auth()->id(),
                        description: $data['description'] ?? null,
                    );

                    app(CloseFiscalYearAction::class)->execute($dto);

                    Notification::make()
                        ->title(__('accounting::fiscal_year.closed_successfully'))
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $fiscalYear]));
                } catch (FiscalYearNotReadyToCloseException $e) {
                    Notification::make()
                        ->title(__('accounting::fiscal_year.close_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get the Reopen Fiscal Year action.
     */
    protected function getReopenFiscalYearAction(): Action
    {
        return Action::make('reopenFiscalYear')
            ->label(__('accounting::fiscal_year.action_reopen'))
            ->icon('heroicon-o-lock-open')
            ->color('info')
            ->visible(fn (): bool => $this->getRecord()->state === FiscalYearState::Closed)
            ->requiresConfirmation()
            ->modalHeading(__('accounting::fiscal_year.reopen_confirmation_title'))
            ->modalDescription(__('accounting::fiscal_year.reopen_confirmation_desc'))
            ->action(function () {
                try {
                    /** @var FiscalYear $fiscalYear */
                    $fiscalYear = $this->getRecord();

                    app(ReopenFiscalYearAction::class)->execute($fiscalYear, auth()->id());

                    Notification::make()
                        ->title(__('accounting::fiscal_year.reopened_successfully'))
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $fiscalYear]));
                } catch (FiscalYearCannotBeReopenedException $e) {
                    Notification::make()
                        ->title(__('accounting::fiscal_year.reopen_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Disable form editing for closed fiscal years.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * Check if form should be disabled.
     */
    public function isFormDisabled(): bool
    {
        return ! $this->getRecord()->state->isEditable();
    }
}
