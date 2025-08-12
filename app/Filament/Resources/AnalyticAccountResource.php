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

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.core_accounting');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytic_account.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('analytic_account.analytic_account');
    }

    public static function getPluralModelLabel(): string
    {
        return __('analytic_account.analytic_accounts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('analytic_account.company'))
                    ->placeholder(__('analytic_account.select_company'))
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->label(__('analytic_account.currency'))
                    ->placeholder(__('analytic_account.select_currency')),
                Forms\Components\TextInput::make('name')
                    ->label(__('analytic_account.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference')
                    ->label(__('analytic_account.reference'))
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('analytic_account.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('analytic_account.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->label(__('analytic_account.currency'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('analytic_account.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('analytic_account.reference'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('analytic_account.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('analytic_account.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('analytic_account.updated_at'))
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
