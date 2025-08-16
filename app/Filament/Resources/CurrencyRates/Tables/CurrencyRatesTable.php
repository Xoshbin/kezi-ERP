<?php

namespace App\Filament\Resources\CurrencyRates\Tables;

use App\Models\Currency;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CurrencyRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency.name')
                    ->label('Currency')
                    ->formatStateUsing(fn ($record) => "{$record->currency->name} ({$record->currency->code})")
                    ->sortable(['currency.name'])
                    ->searchable(['currency.name', 'currency.code']),

                TextColumn::make('rate')
                    ->label('Exchange Rate')
                    ->numeric(decimalPlaces: 6)
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label('Effective Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'api' => 'success',
                        'manual' => 'warning',
                        'bank' => 'primary',
                        'central_bank' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('currency_id')
                    ->label('Currency')
                    ->relationship('currency', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Currency $record): string => "{$record->name} ({$record->code})"),

                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'manual' => 'Manual Entry',
                        'api' => 'API Import',
                        'bank' => 'Bank Rate',
                        'central_bank' => 'Central Bank',
                    ]),

                Filter::make('recent')
                    ->label('Recent (Last 30 days)')
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
