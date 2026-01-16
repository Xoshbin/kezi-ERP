<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\ManufacturingCluster;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\RelationManagers;
use Modules\Manufacturing\Models\BillOfMaterial;

class BillOfMaterialResource extends Resource
{
    protected static ?string $model = BillOfMaterial::class;

    protected static ?string $cluster = ManufacturingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('manufacturing::manufacturing.bom.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('manufacturing::manufacturing.bom.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('manufacturing::manufacturing.bom.section_bom_information'))
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label(__('manufacturing::manufacturing.bom.finished_product'))
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('code')
                        ->label(__('manufacturing::manufacturing.bom.code'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Forms\Components\TextInput::make('name')
                        ->label(__('manufacturing::manufacturing.bom.name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('type')
                        ->label(__('manufacturing::manufacturing.bom.type'))
                        ->options([
                            BOMType::Normal->value => __('manufacturing::manufacturing.bom.types.normal'),
                            BOMType::Kit->value => __('manufacturing::manufacturing.bom.types.kit'),
                            BOMType::Phantom->value => __('manufacturing::manufacturing.bom.types.phantom'),
                        ])
                        ->default(BOMType::Normal->value)
                        ->required(),

                    Forms\Components\TextInput::make('quantity')
                        ->label(__('manufacturing::manufacturing.bom.quantity'))
                        ->numeric()
                        ->default(1.0)
                        ->required()
                        ->minValue(0.0001),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('manufacturing::manufacturing.bom.is_active'))
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('manufacturing::manufacturing.bom.notes'))
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('manufacturing::manufacturing.bom.code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.bom.finished_product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('manufacturing::manufacturing.bom.type'))
                    ->badge()
                    ->formatStateUsing(fn (BOMType $state): string => $state->label()),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('manufacturing::manufacturing.bom.qty'))
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('manufacturing::manufacturing.bom.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('lines_count')
                    ->label(__('manufacturing::manufacturing.bom.components'))
                    ->counts('lines')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('manufacturing::manufacturing.bom.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        BOMType::Normal->value => __('manufacturing::manufacturing.bom.types.normal'),
                        BOMType::Kit->value => __('manufacturing::manufacturing.bom.types.kit'),
                        BOMType::Phantom->value => __('manufacturing::manufacturing.bom.types.phantom'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('manufacturing::manufacturing.bom.is_active'))
                    ->placeholder(__('manufacturing::manufacturing.placeholders.all_boms'))
                    ->trueLabel(__('manufacturing::manufacturing.bom.filters.active_only'))
                    ->falseLabel(__('manufacturing::manufacturing.bom.filters.inactive_only')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillOfMaterials::route('/'),
            'create' => Pages\CreateBillOfMaterial::route('/create'),
            'edit' => Pages\EditBillOfMaterial::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }
}
