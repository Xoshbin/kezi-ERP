<?php

namespace App\Filament\Resources\JournalResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntriesRelationManager extends RelationManager
{
    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('journal.journal_entries');
    }

    protected static string $relationship = 'journalEntries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('entry_date')
                    ->label(__('journal.entry_date'))
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->label(__('journal.reference'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('journal.description'))
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_posted')
                    ->label(__('journal.is_posted'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label(__('journal.entry_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('journal.reference')),
                Tables\Columns\IconColumn::make('is_posted')
                    ->label(__('journal.is_posted'))
                    ->boolean(),
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
