<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntryLines';

    protected static ?string $title = 'Journal Entry Lines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('account.journal_entry_lines.plural_label');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('journal_entry_id')
                    ->label(__('account.journal_entry_lines.journal_entry'))
                    ->relationship('journalEntry', 'reference')
                    ->required(),
                Forms\Components\TextInput::make('debit')
                    ->label(__('account.journal_entry_lines.debit'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('credit')
                    ->label(__('account.journal_entry_lines.credit'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('description')
                    ->label(__('account.journal_entry_lines.description'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('journalEntry.reference')->label(__('account.journal_entry_lines.journal_entry')),
                Tables\Columns\TextColumn::make('debit')->label(__('account.journal_entry_lines.debit')),
                Tables\Columns\TextColumn::make('credit')->label(__('account.journal_entry_lines.credit')),
                Tables\Columns\TextColumn::make('description')->label(__('account.journal_entry_lines.description')),
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
