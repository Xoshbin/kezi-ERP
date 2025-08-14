<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Models\Tax;
use App\Enums\Accounting\TaxType;
use App\Support\NumberFormatter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Concerns\Translatable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxResource extends Resource
{
    use Translatable;

    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Settings::class;

    public static function getLabel(): string
    {
        return __('tax.label');
    }

    public static function getPluralLabel(): string
    {
        return __('tax.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('tax.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tax_account_id')
                    ->relationship('taxAccount', 'name')
                    ->label(__('tax.tax_account'))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('tax.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('rate')
                    ->label(__('tax.rate'))
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('type')
                    ->label(__('tax.type'))
                    ->options(collect(TaxType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('tax.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('taxAccount.name')
                    ->label(__('tax.tax_account'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('tax.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label(__('tax.rate'))
                    ->formatStateUsing(fn ($state) => NumberFormatter::formatPercentage($state / 100))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('tax.type'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('tax.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('tax.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('tax.updated_at'))
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
