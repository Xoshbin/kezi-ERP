<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\RelationManagers;

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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntryLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::analytic_account.relation_managers.journal_entry_lines.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'reference')
                    ->label(__('accounting::analytic_account.journal_entry'))
                    ->required(),
                TextInput::make('debit')
                    ->label(__('accounting::analytic_account.debit'))
                    ->required()
                    ->numeric(),
                TextInput::make('credit')
                    ->label(__('accounting::analytic_account.credit'))
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->label(__('accounting::analytic_account.description'))
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
                    ->label(__('accounting::analytic_account.journal_entry')),
                TextColumn::make('debit')
                    ->label(__('accounting::analytic_account.debit')),
                TextColumn::make('credit')
                    ->label(__('accounting::analytic_account.credit')),
                TextColumn::make('description')
                    ->label(__('accounting::analytic_account.description')),
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
