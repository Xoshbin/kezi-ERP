<?php

namespace App\Filament\Resources\JournalEntries\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')->label(__('journal_entry.account'))->relationship('account', 'name')->required(),
                TextInput::make('debit')->label(__('journal_entry.debit'))->required()->numeric(),
                TextInput::make('credit')->label(__('journal_entry.credit'))->required()->numeric(),
                TextInput::make('description')->label(__('journal_entry.description'))->maxLength(255),
                Select::make('partner_id')->label(__('journal_entry.partner'))->relationship('partner', 'name'),
                Select::make('analytic_account_id')->label(__('journal_entry.analytic_account'))->relationship('analyticAccount', 'name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('account.name')->label(__('journal_entry.account')),
                TextColumn::make('debit')->label(__('journal_entry.debit')),
                TextColumn::make('credit')->label(__('journal_entry.credit')),
                TextColumn::make('description')->label(__('journal_entry.description')),
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
