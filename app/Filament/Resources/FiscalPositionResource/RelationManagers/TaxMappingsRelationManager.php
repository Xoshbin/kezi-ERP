<?php

namespace App\Filament\Resources\FiscalPositionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'taxMappings';

    protected static ?string $title = 'Tax Mappings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('original_tax_id')
                    ->relationship('originalTax', 'name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.original_tax'))
                    ->required(),
                Forms\Components\Select::make('mapped_tax_id')
                    ->relationship('mappedTax', 'name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.mapped_tax'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('originalTax.name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.original_tax')),
                Tables\Columns\TextColumn::make('mappedTax.name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.mapped_tax')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
