<?php

namespace App\Filament\Resources\Accounts\RelationManagers;

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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntryLines';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('account.journal_entry_lines.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('journal_entry_id')
                    ->label(__('account.journal_entry_lines.journal_entry'))
                    ->relationship('journalEntry', 'reference')
                    ->required(),
                TextInput::make('debit')
                    ->label(__('account.journal_entry_lines.debit'))
                    ->required()
                    ->numeric(),
                TextInput::make('credit')
                    ->label(__('account.journal_entry_lines.credit'))
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->label(__('account.journal_entry_lines.description'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('journalEntry.reference')->label(__('account.journal_entry_lines.journal_entry')),
                TextColumn::make('debit')->label(__('account.journal_entry_lines.debit')),
                TextColumn::make('credit')->label(__('account.journal_entry_lines.credit')),
                TextColumn::make('description')->label(__('account.journal_entry_lines.description')),
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
