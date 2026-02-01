<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TaxMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'taxMappings';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::fiscal_position.relation_managers.tax_mappings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('original_tax_id')
                    ->relationship('originalTax', 'name')
                    ->label(__('accounting::fiscal_position.relation_managers.tax_mappings.original_tax'))
                    ->required(),
                Select::make('mapped_tax_id')
                    ->relationship('mappedTax', 'name')
                    ->label(__('accounting::fiscal_position.relation_managers.tax_mappings.mapped_tax'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('originalTax.name')
                    ->label(__('accounting::fiscal_position.relation_managers.tax_mappings.original_tax')),
                TextColumn::make('mappedTax.name')
                    ->label(__('accounting::fiscal_position.relation_managers.tax_mappings.mapped_tax')),
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
