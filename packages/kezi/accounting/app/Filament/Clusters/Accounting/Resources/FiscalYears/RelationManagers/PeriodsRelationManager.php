<?php

declare(strict_types=1);

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Accounting\Actions\Accounting\CloseFiscalPeriodAction;
use Kezi\Accounting\Actions\Accounting\ReopenFiscalPeriodAction;
use Kezi\Accounting\Enums\Accounting\FiscalPeriodState;
use Kezi\Accounting\Exceptions\FiscalPeriodCannotBeReopenedException;
use Kezi\Accounting\Exceptions\FiscalPeriodNotReadyToCloseException;
use Kezi\Accounting\Models\FiscalPeriod;

/**
 * Manages fiscal periods within a fiscal year.
 *
 * Provides a table view of periods with actions to close and reopen
 * individual periods, automatically updating lock dates.
 */
final class PeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::fiscal_period.plural_model_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns($this->buildColumns())
            ->defaultSort('start_date')
            ->actions([
                $this->buildCloseAction(),
                $this->buildReopenAction(),
            ]);
    }

    /**
     * Build the table columns.
     *
     * @return array<TextColumn>
     */
    private function buildColumns(): array
    {
        return [
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
        ];
    }

    /**
     * Build the Close Period action.
     */
    private function buildCloseAction(): Action
    {
        return Action::make('close')
            ->label(__('accounting::fiscal_period.action_close'))
            ->icon('heroicon-o-lock-closed')
            ->color('warning')
            ->visible(fn (FiscalPeriod $record): bool => $record->isOpen())
            ->requiresConfirmation()
            ->modalHeading(__('accounting::fiscal_period.close_confirmation_title'))
            ->modalDescription(__('accounting::fiscal_period.close_confirmation_desc'))
            ->action(fn (FiscalPeriod $record) => $this->closePeriod($record));
    }

    /**
     * Build the Reopen Period action.
     */
    private function buildReopenAction(): Action
    {
        return Action::make('reopen')
            ->label(__('accounting::fiscal_period.action_reopen'))
            ->icon('heroicon-o-lock-open')
            ->color('info')
            ->visible(fn (FiscalPeriod $record): bool => $record->isClosed())
            ->requiresConfirmation()
            ->modalHeading(__('accounting::fiscal_period.reopen_confirmation_title'))
            ->modalDescription(__('accounting::fiscal_period.reopen_confirmation_desc'))
            ->action(fn (FiscalPeriod $record) => $this->reopenPeriod($record));
    }

    /**
     * Execute the close period action with proper error handling.
     */
    private function closePeriod(FiscalPeriod $record): void
    {
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
    }

    /**
     * Execute the reopen period action with proper error handling.
     */
    private function reopenPeriod(FiscalPeriod $record): void
    {
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
    }
}
