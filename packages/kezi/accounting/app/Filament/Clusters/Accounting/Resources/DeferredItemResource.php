<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource\Pages;
use Kezi\Accounting\Models\DeferredItem;

class DeferredItemResource extends Resource
{
    protected static ?string $model = DeferredItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function getNavigationGroup(): ?string
    {
        return __('Accounting');
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getModelLabel(): string
    {
        return __('accounting::deferred_item.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::deferred_item.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\TextInput::make('original_amount')
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('accounting::deferred_item.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('accounting::deferred_item.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'revenue' => 'success',
                        'expense' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('original_amount')
                    ->label(__('accounting::deferred_item.original_amount'))
                    ->money(fn ($record) => $record->company->currency->code),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('accounting::deferred_item.start_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('accounting::deferred_item.end_date'))
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListDeferredItems::route('/'),
            'create' => Pages\CreateDeferredItem::route('/create'),
            'edit' => Pages\EditDeferredItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
