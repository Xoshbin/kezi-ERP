<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('journal_id')
                    ->relationship('journal', 'name')
                    ->required(),
                Forms\Components\TextInput::make('currency_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->default(1.000000),
                Forms\Components\TextInput::make('created_by_user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('entry_date')
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_debit')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_credit')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_posted')
                    ->required(),
                Forms\Components\TextInput::make('hash')
                    ->maxLength(64),
                Forms\Components\TextInput::make('previous_hash')
                    ->maxLength(64),
                Forms\Components\TextInput::make('source_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('source_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exchange_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_debit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_credit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_posted')
                    ->boolean(),
                Tables\Columns\TextColumn::make('hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('previous_hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
