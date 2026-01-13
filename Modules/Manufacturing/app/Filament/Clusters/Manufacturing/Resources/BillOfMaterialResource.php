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
        return __('manufacturing::bom.navigation.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('manufacturing::bom.navigation.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('manufacturing::bom.sections.info'))
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label(__('manufacturing::bom.fields.product'))
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('code')
                        ->label(__('manufacturing::bom.fields.code'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Forms\Components\TextInput::make('name')
                        ->label(__('manufacturing::bom.fields.name'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('type')
                        ->label(__('manufacturing::bom.fields.type'))
                        ->options([
                            BOMType::Normal->value => __('manufacturing::bom.types.normal'),
                            BOMType::Kit->value => __('manufacturing::bom.types.kit'),
                            BOMType::Phantom->value => __('manufacturing::bom.types.phantom'),
                        ])
                        ->default(BOMType::Normal->value)
                        ->required(),

                    Forms\Components\TextInput::make('quantity')
                        ->label(__('manufacturing::bom.fields.quantity'))
                        ->numeric()
                        ->default(1.0)
                        ->required()
                        ->minValue(0.0001),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('manufacturing::bom.fields.is_active'))
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('manufacturing::bom.fields.notes'))
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
                    ->label(__('manufacturing::bom.fields.code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::bom.fields.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('manufacturing::bom.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (BOMType $state): string => match($state) {
                        BOMType::Normal => __('manufacturing::bom.types.normal'),
                        BOMType::Kit => __('manufacturing::bom.types.kit'),
                        BOMType::Phantom => __('manufacturing::bom.types.phantom'),
                        default => $state->label(),
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('manufacturing::bom.fields.qty'))
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('manufacturing::bom.fields.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('lines_count')
                    ->label(__('manufacturing::bom.fields.components'))
                    ->counts('lines')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('manufacturing::bom.fields.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        BOMType::Normal->value => __('manufacturing::bom.types.normal'),
                        BOMType::Kit->value => __('manufacturing::bom.types.kit'),
                        BOMType::Phantom->value => __('manufacturing::bom.types.phantom'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('manufacturing::bom.fields.is_active'))
                    ->placeholder(__('manufacturing::bom.navigation.plural'))
                    ->trueLabel(__('manufacturing::bom.fields.is_active'))
                    ->falseLabel('Inactive only'),
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
