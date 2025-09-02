<?php

namespace App\Filament\Clusters\Settings\Resources\Taxes;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\TaxType;
use App\Filament\Clusters\Settings\Resources\Taxes\Pages\CreateTax;
use App\Filament\Clusters\Settings\Resources\Taxes\Pages\EditTax;
use App\Filament\Clusters\Settings\Resources\Taxes\Pages\ListTaxes;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Support\TranslatableSelect;
use App\Models\Tax;
use App\Support\NumberFormatter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

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
                Section::make(__('tax.basic_information'))
                    ->schema([
                        TranslatableSelect::relationship('company_id','company', \App\Models\Company::class, __('tax.company'))
                            ->createOptionForm([
                                TextInput::make('name')->label(__('company.name'))->required(),
                                TextInput::make('tax_id')->label(__('company.tax_id')),
                                TextInput::make('fiscal_country')->label(__('company.fiscal_country'))->required(),
                                TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('company.currency_id'))->required(),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_company'))
                            ->createOptionAction(fn(\Filament\Actions\Action $a) => $a->modalWidth('lg'))
                            ->required(),

                        TranslatableSelect::relationship('tax_account_id','taxAccount', \App\Models\Account::class, __('tax.tax_account'))
                            ->createOptionForm([
                                Select::make('company_id')->relationship('company', 'name')->label(__('company.name'))->required(),
                                TextInput::make('code')->label(__('account.code'))->required(),
                                TextInput::make('name')->label(__('account.name'))->required(),
                                Select::make('type')->label(__('account.type'))
                                    ->options(collect(AccountType::cases())->mapWithKeys(fn($t)=>[$t->value=>$t->label()]))
                                    ->required(),
                                Toggle::make('is_deprecated')->label(__('account.is_deprecated'))->default(false),
                                Toggle::make('allow_reconciliation')->label(__('account.allow_reconciliation'))->default(false),
                            ])
                            ->createOptionModalHeading(__('common.modal_title_create_account'))
                            ->createOptionAction(fn(\Filament\Actions\Action $a) => $a->modalWidth('lg'))
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
                            ->options(collect(TaxType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('tax.is_active'))
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
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
