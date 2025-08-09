<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FiscalPositionResource\Pages;
use App\Filament\Resources\FiscalPositionResource\RelationManagers;
use App\Models\FiscalPosition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FiscalPositionResource extends Resource
{
    protected static ?string $model = FiscalPosition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('fiscal_position.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fiscal_position.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('fiscal_position.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('fiscal_position.company'))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('fiscal_position.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label(__('fiscal_position.country'))
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('fiscal_position.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('fiscal_position.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label(__('fiscal_position.country'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('fiscal_position.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('fiscal_position.updated_at'))
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
            RelationManagers\TaxMappingsRelationManager::class,
            RelationManagers\AccountMappingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiscalPositions::route('/'),
            'create' => Pages\CreateFiscalPosition::route('/create'),
            'edit' => Pages\EditFiscalPosition::route('/{record}/edit'),
        ];
    }
}
