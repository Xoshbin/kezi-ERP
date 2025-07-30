<?php

namespace App\Filament\Resources\AnalyticAccountResource\RelationManagers;

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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('analytic_account.relation_managers.journal_entry_lines.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'reference')
                    ->label(__('analytic_account.journal_entry'))
                    ->required(),
                Forms\Components\TextInput::make('debit')
                    ->label(__('analytic_account.debit'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('credit')
                    ->label(__('analytic_account.credit'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('description')
                    ->label(__('analytic_account.description'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('journalEntry.reference')
                    ->label(__('analytic_account.journal_entry')),
                Tables\Columns\TextColumn::make('debit')
                    ->label(__('analytic_account.debit')),
                Tables\Columns\TextColumn::make('credit')
                    ->label(__('analytic_account.credit')),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('analytic_account.description')),
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
