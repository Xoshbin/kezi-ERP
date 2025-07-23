<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnalyticAccountResource\Pages;
use App\Filament\Resources\AnalyticAccountResource\RelationManagers;
use App\Filament\Resources\AnalyticAccountResource\RelationManagers\JournalEntryLinesRelationManager;
use App\Filament\Resources\AnalyticAccountResource\RelationManagers\AnalyticPlansRelationManager;
use App\Models\AnalyticAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnalyticAccountResource extends Resource
{
    protected static ?string $model = AnalyticAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            RelationManagers\JournalEntryLinesRelationManager::class,
            RelationManagers\AnalyticPlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnalyticAccounts::route('/'),
            'create' => Pages\CreateAnalyticAccount::route('/create'),
            'edit' => Pages\EditAnalyticAccount::route('/{record}/edit'),
        ];
    }
}
