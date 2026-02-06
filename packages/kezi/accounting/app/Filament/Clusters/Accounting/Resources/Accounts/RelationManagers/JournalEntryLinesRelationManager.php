<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\RelationManagers;

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

/**
 * @extends RelationManager<\Kezi\Accounting\Models\Account>
 */
class JournalEntryLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntryLines';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::account.journal_entry_lines.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('journal_entry_id')
                    ->label(__('accounting::account.journal_entry_lines.journal_entry'))
                    ->relationship('journalEntry', 'reference')
                    ->required(),
                TextInput::make('debit')
                    ->label(__('accounting::account.journal_entry_lines.debit'))
                    ->required()
                    ->numeric(),
                TextInput::make('credit')
                    ->label(__('accounting::account.journal_entry_lines.credit'))
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->label(__('accounting::account.journal_entry_lines.description'))
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['journalEntry.company.currency']))
            ->columns([
                TextColumn::make('journalEntry.reference')->label(__('accounting::account.journal_entry_lines.journal_entry')),
                TextColumn::make('debit')->label(__('accounting::account.journal_entry_lines.debit')),
                TextColumn::make('credit')->label(__('accounting::account.journal_entry_lines.credit')),
                TextColumn::make('description')->label(__('accounting::account.journal_entry_lines.description')),
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
