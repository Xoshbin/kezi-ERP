<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Product\Filament\Forms\Components\ProductSelectField;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('manufacturing::manufacturing.bom.components');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ProductSelectField::make('product_id')
                ->label(__('manufacturing::manufacturing.bom.component'))
                ->required(),

            Forms\Components\TextInput::make('quantity')
                ->label(__('manufacturing::manufacturing.bom.quantity'))
                ->numeric()
                ->required()
                ->minValue(0.0001)
                ->default(1.0),

            Forms\Components\TextInput::make('unit_cost')
                ->label(__('manufacturing::manufacturing.bom.unit_cost'))
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(0),

            Forms\Components\Select::make('work_center_id')
                ->label(__('manufacturing::manufacturing.bom.work_center'))
                ->relationship('workCenter', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('manufacturing::manufacturing.bom.component'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('manufacturing::manufacturing.bom.quantity'))
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label(__('manufacturing::manufacturing.bom.unit_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD'),

                Tables\Columns\TextColumn::make('workCenter.name')
                    ->label(__('manufacturing::manufacturing.bom.work_center'))
                    ->default('—'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
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
}
