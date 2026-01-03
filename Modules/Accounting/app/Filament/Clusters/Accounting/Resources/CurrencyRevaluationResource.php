<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources;

use Filament\Resources\Resource;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource\Pages;
use Modules\Accounting\Models\CurrencyRevaluation;

class CurrencyRevaluationResource extends Resource
{
    protected static ?string $model = CurrencyRevaluation::class;

    protected static ?string $cluster = AccountingCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedCurrencyDollar;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\DatePicker::make('revaluation_date')
                    ->required()
                    ->default(now()),
                \Filament\Forms\Components\TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('revaluation_date')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (\Modules\Accounting\Enums\Currency\RevaluationStatus $state): string => $state->color())
                    ->formatStateUsing(fn (\Modules\Accounting\Enums\Currency\RevaluationStatus $state): string => $state->label()),
                \Filament\Tables\Columns\TextColumn::make('total_gain')
                    ->money(fn ($record) => $record->company->currency->code),
                \Filament\Tables\Columns\TextColumn::make('total_loss')
                    ->money(fn ($record) => $record->company->currency->code),
                \Filament\Tables\Columns\TextColumn::make('net_adjustment')
                    ->money(fn ($record) => $record->company->currency->code),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencyRevaluations::route('/'),
            'create' => Pages\CreateCurrencyRevaluation::route('/create'),
        ];
    }
}
