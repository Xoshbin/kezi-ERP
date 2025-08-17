<?php

namespace App\Filament\Resources\FiscalPositions\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'taxMappings';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('fiscal_position.relation_managers.tax_mappings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('original_tax_id')
                    ->relationship('originalTax', 'name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.original_tax'))
                    ->required(),
                Select::make('mapped_tax_id')
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
                TextColumn::make('originalTax.name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.original_tax')),
                TextColumn::make('mappedTax.name')
                    ->label(__('fiscal_position.relation_managers.tax_mappings.mapped_tax')),
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
