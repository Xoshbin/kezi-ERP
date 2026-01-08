<?php

namespace Modules\Manufacturing\Filament\Resources;

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
use Modules\Manufacturing\Filament\Resources\BillOfMaterialResource\Pages;
use Modules\Manufacturing\Filament\Resources\BillOfMaterialResource\RelationManagers;
use Modules\Manufacturing\Models\BillOfMaterial;

class BillOfMaterialResource extends Resource
{
    protected static ?string $model = BillOfMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Bill of Material';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Bills of Materials';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('BOM Information')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Finished Product')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('code')
                        ->label('BOM Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Forms\Components\TextInput::make('name')
                        ->label('BOM Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('type')
                        ->label('BOM Type')
                        ->options([
                            BOMType::Normal->value => 'Normal',
                            BOMType::Kit->value => 'Kit',
                            BOMType::Phantom->value => 'Phantom',
                        ])
                        ->default(BOMType::Normal->value)
                        ->required(),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity to Produce')
                        ->numeric()
                        ->default(1.0)
                        ->required()
                        ->minValue(0.0001),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
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
                    ->label('BOM Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Finished Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (BOMType $state): string => $state->label()),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('lines_count')
                    ->label('Components')
                    ->counts('lines')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        BOMType::Normal->value => 'Normal',
                        BOMType::Kit->value => 'Kit',
                        BOMType::Phantom->value => 'Phantom',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All BOMs')
                    ->trueLabel('Active only')
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
