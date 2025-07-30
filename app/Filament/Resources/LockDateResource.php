<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LockDateResource\Pages;
use App\Filament\Resources\LockDateResource\RelationManagers;
use App\Models\LockDate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LockDateResource extends Resource
{
    protected static ?string $model = LockDate::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    public static function getLabel(): string
    {
        return __('lock_date.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lock_date.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lock_date.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('lock_date.navigation_group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('lock_date.company'))
                    ->placeholder(__('lock_date.select_company'))
                    ->required(),
                Forms\Components\TextInput::make('lock_type')
                    ->label(__('lock_date.lock_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('locked_until')
                    ->label(__('lock_date.locked_until'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('lock_date.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lock_type')
                    ->label(__('lock_date.lock_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('locked_until')
                    ->label(__('lock_date.locked_until'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('lock_date.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('lock_date.updated_at'))
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
            'index' => Pages\ListLockDates::route('/'),
            'create' => Pages\CreateLockDate::route('/create'),
            'edit' => Pages\EditLockDate::route('/{record}/edit'),
        ];
    }
}
