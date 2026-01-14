<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
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
use Modules\Accounting\Enums\Accounting\WithholdingTaxApplicability;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\CreateWithholdingTaxType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\EditWithholdingTaxType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\ListWithholdingTaxTypes;
use Modules\Accounting\Models\WithholdingTaxType;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class WithholdingTaxTypeResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    use Translatable;

    protected static ?string $model = WithholdingTaxType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 4;

    public static function getLabel(): string
    {
        return __('accounting::withholding_tax.type_label');
    }

    public static function getPluralLabel(): string
    {
        return __('accounting::withholding_tax.types_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::withholding_tax.types_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.groups.accounting_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::withholding_tax.basic_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('accounting::withholding_tax.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rate')
                            ->label(__('accounting::withholding_tax.rate'))
                            ->helperText(__('accounting::withholding_tax.rate_help'))
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        TranslatableSelect::make('withholding_account_id')
                            ->searchable()
                            ->preload()
                            ->relationship('withholdingAccount', 'name')
                            ->label(__('accounting::withholding_tax.withholding_account'))
                            ->createOptionForm([
                                Select::make('company_id')->relationship('company', 'name')->label(__('company.name'))->required(),
                                TextInput::make('code')->label(__('accounting::account.code'))->required(),
                                TextInput::make('name')->label(__('accounting::account.name'))->required(),
                                Select::make('type')->label(__('accounting::account.type'))
                                    ->options(collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                                    ->required(),
                                Toggle::make('is_deprecated')->label(__('accounting::account.is_deprecated'))->default(false),
                            ])
                            ->createOptionModalHeading(__('accounting::common.modal_title_create_account'))
                            ->createOptionAction(fn (Action $a) => $a->name('create-account-option')->modalWidth('lg'))
                            ->required(),
                        Select::make('applicable_to')
                            ->label(__('accounting::withholding_tax.applicable_to'))
                            ->options(collect(WithholdingTaxApplicability::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                            ->required()
                            ->default(WithholdingTaxApplicability::Both->value),
                        TextInput::make('threshold_amount')
                            ->label(__('accounting::withholding_tax.threshold_amount'))
                            ->helperText(__('accounting::withholding_tax.threshold_help'))
                            ->numeric()
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label(__('accounting::withholding_tax.is_active'))
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
                TextColumn::make('name')
                    ->label(__('accounting::withholding_tax.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate')
                    ->label(__('accounting::withholding_tax.rate'))
                    ->formatStateUsing(fn ($state) => \Modules\Foundation\Support\NumberFormatter::formatPercentage($state * 100))
                    ->sortable(),
                TextColumn::make('applicable_to')
                    ->label(__('accounting::withholding_tax.applicable_to'))
                    ->formatStateUsing(fn ($state) => $state instanceof WithholdingTaxApplicability ? $state->label() : $state)
                    ->searchable(),
                TextColumn::make('withholdingAccount.name')
                    ->label(__('accounting::withholding_tax.withholding_account'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('accounting::withholding_tax.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('accounting::withholding_tax.created_at'))
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
            'index' => ListWithholdingTaxTypes::route('/'),
            'create' => CreateWithholdingTaxType::route('/create'),
            'edit' => EditWithholdingTaxType::route('/{record}/edit'),
        ];
    }
}
