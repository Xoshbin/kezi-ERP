<?php

namespace App\Filament\Resources\Taxes;

use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Taxes\Pages\ListTaxes;
use App\Filament\Resources\Taxes\Pages\CreateTax;
use App\Filament\Resources\Taxes\Pages\EditTax;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Models\Tax;
use App\Enums\Accounting\TaxType;
use App\Support\NumberFormatter;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxResource extends Resource
{
    use Translatable;

    protected static ?string $model = Tax::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('tax.company'))
                    ->required(),
                Select::make('tax_account_id')
                    ->relationship('taxAccount', 'name')
                    ->label(__('tax.tax_account'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('tax.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('rate')
                    ->label(__('tax.rate'))
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->label(__('tax.type'))
                    ->options(collect(TaxType::cases())->mapWithKeys(fn($case) => [$case->value => $case->label()]))
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('tax.is_active'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('tax.company'))
                    ->sortable(),
                TextColumn::make('taxAccount.name')
                    ->label(__('tax.tax_account'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('tax.name'))
                    ->searchable(),
                TextColumn::make('rate')
                    ->label(__('tax.rate'))
                    ->formatStateUsing(fn ($state) => NumberFormatter::formatPercentage($state / 100))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('tax.type'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('tax.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('tax.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('tax.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListTaxes::route('/'),
            'create' => CreateTax::route('/create'),
            'edit' => EditTax::route('/{record}/edit'),
        ];
    }
}
