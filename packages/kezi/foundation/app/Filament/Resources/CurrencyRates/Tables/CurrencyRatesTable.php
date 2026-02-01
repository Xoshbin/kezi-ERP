<?php

namespace Kezi\Foundation\Filament\Resources\CurrencyRates\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Support\TranslatableHelper;

class CurrencyRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency.name')
                    ->label(__('foundation::currency.exchange_rates.currency'))
                    ->formatStateUsing(fn ($record) => "{$record->currency->name} ({$record->currency->code})")
                    ->sortable(['currency.name'])
                    ->searchable(['currency.name', 'currency.code']),

                TextColumn::make('rate')
                    ->label(__('foundation::currency.exchange_rates.rate'))
                    ->numeric(decimalPlaces: 6)
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label(__('foundation::currency.exchange_rates.effective_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('source')
                    ->label(__('foundation::currency.exchange_rates.source'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("foundation::currency.exchange_rates.sources.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'api' => 'success',
                        'manual' => 'warning',
                        'bank' => 'primary',
                        'central_bank' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label(__('foundation::currency.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('currency_id')
                    ->label(__('foundation::currency.exchange_rates.currency'))
                    ->relationship('currency', 'name')
                    ->getOptionLabelFromRecordUsing(function (Currency $record): string {
                        $currencyName = TranslatableHelper::getLocalizedValue($record->name);

                        return "{$currencyName} ({$record->code})";
                    }),

                SelectFilter::make('source')
                    ->label(__('foundation::currency.exchange_rates.source'))
                    ->options([
                        'manual' => __('foundation::currency.exchange_rates.sources.manual'),
                        'api' => __('foundation::currency.exchange_rates.sources.api'),
                        'bank' => __('foundation::currency.exchange_rates.sources.bank'),
                        'central_bank' => __('foundation::currency.exchange_rates.sources.central_bank'),
                    ]),

                Filter::make('recent')
                    ->label(__('foundation::currency.exchange_rates.recent_filter'))
                    ->query(fn (Builder $query): Builder => $query->where('effective_date', '>=', Carbon::now()->subDays(30))),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('effective_date', 'desc');
    }
}
