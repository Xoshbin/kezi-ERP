<?php

namespace App\Filament\Resources\AnalyticAccounts\RelationManagers;

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

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('analytic_account.relation_managers.journal_entry_lines.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'reference')
                    ->label(__('analytic_account.journal_entry'))
                    ->required(),
                TextInput::make('debit')
                    ->label(__('analytic_account.debit'))
                    ->required()
                    ->numeric(),
                TextInput::make('credit')
                    ->label(__('analytic_account.credit'))
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->label(__('analytic_account.description'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['journalEntry.company.currency']))
            ->columns([
                TextColumn::make('journalEntry.reference')
                    ->label(__('analytic_account.journal_entry')),
                TextColumn::make('debit')
                    ->label(__('analytic_account.debit')),
                TextColumn::make('credit')
                    ->label(__('analytic_account.credit')),
                TextColumn::make('description')
                    ->label(__('analytic_account.description')),
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
