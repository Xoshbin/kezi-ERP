<?php

namespace Jmeryar\Product\Filament\Resources;

use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Jmeryar\Product\Models\ProductAttribute;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationGroup(): string
    {
        return __('product::navigation.groups.product_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options([
                                'select' => __('product::product.attribute_types.select'),
                                'color' => __('product::product.attribute_types.color'),
                                'radio' => __('product::product.attribute_types.radio'),
                            ])
                            ->required()
                            ->default('select'),
                        TextInput::make('sort_order')
                            ->integer()
                            ->default(0),
                        Checkbox::make('is_active')
                            ->label(__('product::product.is_active'))
                            ->default(true),
                    ])->columns(2),

                Section::make(__('product::product.values'))
                    ->schema([
                        Repeater::make('values')
                            ->relationship('values')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('color_code')
                                    ->label(__('product::product.color_code'))
                                    ->visible(fn ($get) => $get('../../type') === 'color'),
                                TextInput::make('sort_order')
                                    ->integer()
                                    ->default(0),
                                Checkbox::make('is_active')
                                    ->label(__('product::product.is_active'))
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('values_count')
                    ->counts('values')
                    ->label(__('product::product.values')),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('product::product.is_active')),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Jmeryar\Product\Filament\Resources\ProductAttributeResource\Pages\ManageProductAttributes::route('/'),
        ];
    }
}
