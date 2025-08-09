<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('product.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company_id')
                    ->label(__('product.company_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('income_account_id')
                    ->label(__('product.income_account_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('expense_account_id')
                    ->label(__('product.expense_account_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('name')
                    ->label(__('product.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sku')
                    ->label(__('product.sku_label'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('product.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->label(__('product.type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('product.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_id')
                    ->label(__('product.company_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('income_account_id')
                    ->label(__('product.income_account_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expense_account_id')
                    ->label(__('product.expense_account_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('product.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label(__('product.sku_column'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label(__('product.unit_price'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('product.type'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('product.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('product.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('product.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('product.deleted_at'))
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
