<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Accounting\Actions\Accounting\CloseFiscalPeriodAction;
use Modules\Accounting\Actions\Accounting\ReopenFiscalPeriodAction;
use Modules\Accounting\Enums\Accounting\FiscalPeriodState;
use Modules\Accounting\Exceptions\FiscalPeriodCannotBeReopenedException;
use Modules\Accounting\Exceptions\FiscalPeriodNotReadyToCloseException;
use Modules\Accounting\Models\FiscalPeriod;

class PeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::fiscal_period.plural_model_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('accounting::fiscal_period.field_name'))
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('accounting::fiscal_period.field_start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('accounting::fiscal_period.field_end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('state')
                    ->label(__('accounting::fiscal_period.field_state'))
                    ->badge()
                    ->formatStateUsing(fn (FiscalPeriodState $state): string => $state->label())
                    ->color(fn (FiscalPeriodState $state): string => $state->color()),
            ])
            ->defaultSort('start_date')
            ->actions([
                Action::make('close')
                    ->label(__('accounting::fiscal_period.action_close'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (FiscalPeriod $record): bool => $record->isOpen())
                    ->requiresConfirmation()
                    ->modalHeading(__('accounting::fiscal_period.close_confirmation_title'))
                    ->modalDescription(__('accounting::fiscal_period.close_confirmation_desc'))
                    ->action(function (FiscalPeriod $record) {
                        try {
                            app(CloseFiscalPeriodAction::class)->execute($record);

                            Notification::make()
                                ->title(__('accounting::fiscal_period.closed_successfully'))
                                ->success()
                                ->send();
                        } catch (FiscalPeriodNotReadyToCloseException $e) {
                            Notification::make()
                                ->title(__('accounting::fiscal_period.close_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reopen')
                    ->label(__('accounting::fiscal_period.action_reopen'))
                    ->icon('heroicon-o-lock-open')
                    ->color('info')
                    ->visible(fn (FiscalPeriod $record): bool => $record->isClosed())
                    ->requiresConfirmation()
                    ->modalHeading(__('accounting::fiscal_period.reopen_confirmation_title'))
                    ->modalDescription(__('accounting::fiscal_period.reopen_confirmation_desc'))
                    ->action(function (FiscalPeriod $record) {
                        try {
                            app(ReopenFiscalPeriodAction::class)->execute($record);

                            Notification::make()
                                ->title(__('accounting::fiscal_period.reopened_successfully'))
                                ->success()
                                ->send();
                        } catch (FiscalPeriodCannotBeReopenedException $e) {
                            Notification::make()
                                ->title(__('accounting::fiscal_period.reopen_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
