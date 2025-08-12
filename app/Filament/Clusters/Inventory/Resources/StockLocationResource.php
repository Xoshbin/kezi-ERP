<?php

namespace App\Filament\Clusters\Inventory\Resources;

use App\Enums\Inventory\StockLocationType;
use App\Filament\Clusters\Inventory\Resources\StockLocationResource\Pages;
use App\Models\Company;
use App\Models\StockLocation;
use App\Filament\Clusters\Inventory;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockLocationResource extends Resource
{
    protected static ?string $model = StockLocation::class;

    protected static ?string $cluster = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('stock_location.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock_location.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('stock_location.plural_label');
    }

    public static function form(Form $form): Form
    {
        $company = Company::first();

        return $form->schema([
            Section::make(__('stock_location.basic_information'))
                ->description(__('stock_location.basic_information_description'))
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('stock_location.company'))
                            ->required()
                            ->searchable()
                            ->default($company?->id)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('company.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('address')
                                    ->label(__('company.address'))
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tax_id')
                                    ->label(__('company.tax_id'))
                                    ->maxLength(255),
                                Forms\Components\Select::make('currency_id')
                                    ->label(__('company.currency_id'))
                                    ->relationship('currency', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('fiscal_country')
                                    ->label(__('company.fiscal_country'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_company'))
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('lg');
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label(__('stock_location.name'))
                            ->required()
                            ->maxLength(255),
                    ]),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('stock_location.type'))
                            ->required()
                            ->options(
                                collect(StockLocationType::cases())
                                    ->mapWithKeys(fn (StockLocationType $type) => [$type->value => $type->label()])
                            )
                            ->searchable(),
                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label(__('stock_location.parent'))
                            ->searchable()
                            ->preload()
                            ->helperText(__('stock_location.parent_help')),
                    ]),
                ]),

            Section::make(__('stock_location.status'))
                ->description(__('stock_location.status_description'))
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('stock_location.is_active'))
                        ->default(true)
                        ->helperText(__('stock_location.is_active_help')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('stock_location.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('stock_location.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('stock_location.type'))
                    ->badge()
                    ->formatStateUsing(fn (StockLocationType $state): string => $state->label())
                    ->color(fn (StockLocationType $state): string => match ($state) {
                        StockLocationType::Internal => 'primary',
                        StockLocationType::Customer => 'success',
                        StockLocationType::Vendor => 'warning',
                        StockLocationType::InventoryAdjustment => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('stock_location.parent'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('children_count')
                    ->label(__('stock_location.children_count'))
                    ->counts('children')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('stock_location.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('stock_location.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('stock_location.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('stock_location.company'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('stock_location.type'))
                    ->options(
                        collect(StockLocationType::cases())
                            ->mapWithKeys(fn (StockLocationType $type) => [$type->value => $type->label()])
                    )
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('stock_location.is_active'))
                    ->placeholder(__('stock_location.all_locations'))
                    ->trueLabel(__('stock_location.active_locations'))
                    ->falseLabel(__('stock_location.inactive_locations')),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label(__('stock_location.parent'))
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
            'index' => Pages\ListStockLocations::route('/'),
            'create' => Pages\CreateStockLocation::route('/create'),
            'view' => Pages\ViewStockLocation::route('/{record}'),
            'edit' => Pages\EditStockLocation::route('/{record}/edit'),
        ];
    }
}
