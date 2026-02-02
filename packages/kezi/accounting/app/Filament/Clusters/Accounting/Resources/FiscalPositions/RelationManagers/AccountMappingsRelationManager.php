<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\RelationManagers;

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

class AccountMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'accountMappings';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::fiscal_position.relation_managers.account_mappings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('original_account_id')
                    ->relationship('originalAccount', 'name')
                    ->label(__('accounting::fiscal_position.relation_managers.account_mappings.original_account'))
                    ->required(),
                Select::make('mapped_account_id')
                    ->relationship('mappedAccount', 'name')
                    ->label(__('accounting::fiscal_position.relation_managers.account_mappings.mapped_account'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('originalAccount.name')
                    ->label(__('accounting::fiscal_position.relation_managers.account_mappings.original_account')),
                TextColumn::make('mappedAccount.name')
                    ->label(__('accounting::fiscal_position.relation_managers.account_mappings.mapped_account')),
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
