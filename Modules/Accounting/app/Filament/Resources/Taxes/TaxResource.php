<?php

namespace Modules\Accounting\Filament\Resources\Taxes;

use BackedEnum;
use NumberFormatter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Modules\Accounting\Models\Tax;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Modules\Accounting\Enums\Accounting\TaxType;
use App\Filament\Clusters\Settings\SettingsCluster;
use Modules\Accounting\Enums\Accounting\AccountType;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\Taxes\Pages\EditTax;
use Modules\Accounting\Filament\Resources\Taxes\Pages\CreateTax;
use Modules\Accounting\Filament\Resources\Taxes\Pages\ListTaxes;

class TaxResource extends Resource
{
    use Translatable;

    protected static ?string $model = Tax::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getLabel(): string
    {
        return __('accounting::tax.label');
    }

    public static function getPluralLabel(): string
    {
        return __('accounting::tax.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::tax.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::tax.basic_information'))
                    ->schema([
                        TranslatableSelect::make('tax_account_id')
                            ->searchable()
                            ->preload()
                            ->relationship('taxAccount', 'name')
                            ->label(__('accounting::tax.tax_account'))
                            ->createOptionForm([
                                Select::make('company_id')->relationship('company', 'name')->label(__('company.name'))->required(),
                                TextInput::make('code')->label(__('accounting::account.code'))->required(),
                                TextInput::make('name')->label(__('accounting::account.name'))->required(),
                                Select::make('type')->label(__('accounting::account.type'))
                                    ->options(collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()]))
                                    ->required(),
                                Toggle::make('is_deprecated')->label(__('accounting::account.is_deprecated'))->default(false),
                                Toggle::make('allow_reconciliation')->label(__('accounting::account.allow_reconciliation'))->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(fn(Action $a) => $a->name('create-account-option')->modalWidth('lg'))
                            ->required(),

                        TextInput::make('name')
                            ->label(__('accounting::tax.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rate')
                            ->label(__('accounting::tax.rate'))
                            ->required()
                            ->numeric(),
                        Select::make('type')
                            ->label(__('accounting::tax.type'))
                            ->options(collect(TaxType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('accounting::tax.is_active'))
                            ->required(),
                        Toggle::make('is_recoverable')
                            ->label(__('accounting::tax.is_recoverable'))
                            ->helperText(__('accounting::tax.is_recoverable_help'))
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('taxAccount.name')
                    ->label(__('accounting::tax.tax_account'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('accounting::tax.name'))
                    ->searchable(),
                TextColumn::make('rate')
                    ->label(__('accounting::tax.rate'))
                    ->formatStateUsing(fn($state) => \Modules\Foundation\Support\NumberFormatter::formatPercentage($state / 100))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('accounting::tax.type'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('accounting::tax.is_active'))
                    ->boolean(),
                IconColumn::make('is_recoverable')
                    ->label(__('accounting::tax.is_recoverable'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('accounting::tax.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::tax.updated_at'))
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
