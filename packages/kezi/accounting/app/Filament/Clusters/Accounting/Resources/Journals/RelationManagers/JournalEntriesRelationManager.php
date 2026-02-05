<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Journals\RelationManagers;

use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\CreateAction;
use \Filament\Actions\DeleteAction;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends RelationManager<\Kezi\Accounting\Models\Journal>
 */
class JournalEntriesRelationManager extends RelationManager
{
    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::journal.journal_entries');
    }

    protected static string $relationship = 'journalEntries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('entry_date')
                    ->label(__('accounting::journal.entry_date'))
                    ->required(),
                TextInput::make('reference')
                    ->label(__('accounting::journal.reference'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('accounting::journal.description'))
                    ->columnSpanFull(),
                Toggle::make('is_posted')
                    ->label(__('accounting::journal.is_posted'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('entry_date')
                    ->label(__('accounting::journal.entry_date'))
                    ->date(),
                TextColumn::make('reference')
                    ->label(__('accounting::journal.reference')),
                IconColumn::make('is_posted')
                    ->label(__('accounting::journal.is_posted'))
                    ->boolean(),
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
