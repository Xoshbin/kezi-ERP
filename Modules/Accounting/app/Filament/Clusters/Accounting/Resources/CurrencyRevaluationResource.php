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

    public static function getModelLabel(): string
    {
        return __('accounting::currency_revaluation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::currency_revaluation.plural_label');
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                \Filament\Forms\Components\DatePicker::make('revaluation_date')
                    ->label(__('accounting::currency_revaluation.fields.revaluation_date'))
                    ->required()
                    ->default(now()),
                \Filament\Forms\Components\TextInput::make('description')
                    ->label(__('accounting::currency_revaluation.fields.description'))
                    ->maxLength(255),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('revaluation_date')
                    ->label(__('accounting::currency_revaluation.fields.revaluation_date'))
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('description')
                    ->label(__('accounting::currency_revaluation.fields.description'))
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label(__('accounting::currency_revaluation.fields.status'))
                    ->badge()
                    ->color(fn (\Modules\Accounting\Enums\Currency\RevaluationStatus $state): string => $state->color())
                    ->formatStateUsing(fn (\Modules\Accounting\Enums\Currency\RevaluationStatus $state): string => $state->label()),
                \Filament\Tables\Columns\TextColumn::make('total_gain')
                    ->label(__('accounting::currency_revaluation.fields.total_gain'))
                    ->money(fn ($record) => $record->company->currency->code),
                \Filament\Tables\Columns\TextColumn::make('total_loss')
                    ->label(__('accounting::currency_revaluation.fields.total_loss'))
                    ->money(fn ($record) => $record->company->currency->code),
                \Filament\Tables\Columns\TextColumn::make('net_adjustment')
                    ->label(__('accounting::currency_revaluation.fields.net_adjustment'))
                    ->money(fn ($record) => $record->company->currency->code),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label(__('accounting::currency_revaluation.fields.status'))
                    ->options([
                        'draft' => 'Draft', // Should ideally use Enums or translation, but enum labels are usually translated inside the Enum
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()->label(__('accounting::currency_revaluation.actions.view')),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label(__('accounting::currency_revaluation.actions.delete_bulk')),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
