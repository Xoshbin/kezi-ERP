<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\RelationManagers;

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
                ->label('Component Product')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('quantity_required')
                ->label('Quantity Required')
                ->numeric()
                ->required()
                ->minValue(0.0001),

            Forms\Components\TextInput::make('quantity_consumed')
                ->label('Quantity Consumed')
                ->numeric()
                ->default(0)
                ->disabled(),

            Forms\Components\TextInput::make('unit_cost')
                ->label('Unit Cost')
                ->numeric()
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Component')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_required')
                    ->label('Qty Required')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_consumed')
                    ->label('Qty Consumed')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->getStateUsing(fn ($record) => $record->quantity_consumed * ($record->unit_cost ?? 0))
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->getOwnerRecord()->status->value === 'draft'),
                ]),
            ]);
    }
}
