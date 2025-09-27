<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepreciationEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'depreciationEntries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('depreciation_date')
                    ->label(__('asset.depreciation_date'))
                    ->required(),
                TextInput::make('amount')
                    ->label(__('asset.amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->label(__('asset.status'))
                    ->required()
                    ->maxLength(255)
                    ->default('Draft'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('depreciation_date')
            ->columns([
                TextColumn::make('depreciation_date')
                    ->label(__('asset.depreciation_date'))
                    ->date(),
                TextColumn::make('amount')
                    ->label(__('asset.amount')),
                TextColumn::make('status')
                    ->label(__('asset.status')),
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
