<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')->label(__('accounting::journal_entry.account'))->relationship('account', 'name')->required(),
                TextInput::make('debit')->label(__('accounting::journal_entry.debit'))->required()->numeric(),
                TextInput::make('credit')->label(__('accounting::journal_entry.credit'))->required()->numeric(),
                TextInput::make('description')->label(__('accounting::journal_entry.description'))->maxLength(255),
                Select::make('partner_id')->label(__('accounting::journal_entry.partner'))->relationship('partner', 'name'),
                Select::make('analytic_account_id')->label(__('accounting::journal_entry.analytic_account'))->relationship('analyticAccount', 'name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('account.name')->label(__('accounting::journal_entry.account')),
                TextColumn::make('debit')->label(__('accounting::journal_entry.debit')),
                TextColumn::make('credit')->label(__('accounting::journal_entry.credit')),
                TextColumn::make('description')->label(__('accounting::journal_entry.description')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
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
}
