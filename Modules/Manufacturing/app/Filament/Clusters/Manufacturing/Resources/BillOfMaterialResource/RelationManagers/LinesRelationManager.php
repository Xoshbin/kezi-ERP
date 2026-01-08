<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\RelationManagers;

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

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Components';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('product_id')
                ->label('Component')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->required()
                ->minValue(0.0001)
                ->default(1.0),

            Forms\Components\TextInput::make('unit_cost')
                ->label('Unit Cost')
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(0),

            Forms\Components\Select::make('work_center_id')
                ->label('Work Center')
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
                    ->label('Component')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(decimalPlaces: 4),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money(fn ($record) => $record->currency_code ?? 'USD'),

                Tables\Columns\TextColumn::make('workCenter.name')
                    ->label('Work Center')
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
