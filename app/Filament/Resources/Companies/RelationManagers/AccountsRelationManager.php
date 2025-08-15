<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
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

class AccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accounts';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('company.accounts.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('company.accounts.code'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label(__('company.accounts.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('type')
                    ->label(__('company.accounts.type'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_deprecated')
                    ->label(__('company.accounts.is_deprecated'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->label(__('company.accounts.code')),
                TextColumn::make('name')
                    ->label(__('company.accounts.name')),
                TextColumn::make('type')
                    ->label(__('company.accounts.type')),
                IconColumn::make('is_deprecated')
                    ->label(__('company.accounts.is_deprecated'))
                    ->boolean(),
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
