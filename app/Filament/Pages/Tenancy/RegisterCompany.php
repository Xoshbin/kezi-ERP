<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use App\Services\Onboarding\CompanySeederService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Company';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('identity')
                        ->label(__('company.wizard.identity'))
                        ->description(__('company.wizard.identity_desc'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('company.name'))
                                ->placeholder('e.g. Acme Corp')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('tax_id')
                                ->label(__('company.tax_id'))
                                ->placeholder('e.g. 123-456-789'),
                            TextInput::make('address')
                                ->label(__('company.address'))
                                ->placeholder('e.g. 123 Main St, Erbil'),
                        ]),
                    Step::make('foundation')
                        ->label(__('company.wizard.foundation'))
                        ->description(__('company.wizard.foundation_desc'))
                        ->schema([
                            TranslatableSelect::make('currency_id')
                                ->relationship('currency', 'name')
                                ->label(__('company.currency_id'))
                                ->searchableFields(['name', 'code'])
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('code')
                                        ->label(__('currency.code'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('name')
                                        ->label(__('currency.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('symbol')
                                        ->label(__('currency.symbol'))
                                        ->required()
                                        ->maxLength(5),
                                    TextInput::make('exchange_rate')
                                        ->label(__('currency.exchange_rate'))
                                        ->required()
                                        ->numeric()
                                        ->default(1.0000)
                                        ->step(0.0001),
                                    Toggle::make('is_active')
                                        ->label(__('currency.is_active'))
                                        ->default(true),
                                    TextInput::make('decimal_places')
                                        ->label(__('currency.decimal_places'))
                                        ->required()
                                        ->numeric()
                                        ->default(2)
                                        ->minValue(0)
                                        ->maxValue(4),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_currency')),
                            TextInput::make('fiscal_country')
                                ->label(__('company.fiscal_country'))
                                ->required()
                                ->maxLength(255)
                                ->default('IQ')
                                ->helperText('Country code for fiscal regulations (e.g., IQ for Iraq)'),
                        ]),
                    Step::make('profile')
                        ->label(__('company.wizard.profile'))
                        ->description(__('company.wizard.profile_desc'))
                        ->schema([
                            Select::make('industry_type')
                                ->label(__('company.industry_type'))
                                ->options([
                                    'generic' => __('company.industries.generic'),
                                    'retail' => __('company.industries.retail'),
                                    'manufacturing' => __('company.industries.manufacturing'),
                                    'services' => __('company.industries.services'),
                                ])
                                ->default('generic')
                                ->required()
                                ->native(false),
                            Select::make('inventory_accounting_mode')
                                ->label(__('company.inventory_accounting_mode'))
                                ->options(InventoryAccountingMode::class)
                                ->default(InventoryAccountingMode::AUTO_RECORD_ON_BILL->value)
                                ->required()
                                ->native(false),
                        ]),
                    Step::make('customization')
                        ->label(__('company.wizard.customization'))
                        ->description(__('company.wizard.customization_desc'))
                        ->schema([
                            Toggle::make('seed_sample_data')
                                ->label(__('company.wizard.seed_sample_data'))
                                ->default(true)
                                ->helperText(__('company.wizard.seed_sample_data_help')),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $seedSampleData = (bool) ($data['seed_sample_data'] ?? true);
        unset($data['seed_sample_data']);

        $company = Company::create($data);

        $company->users()->attach(auth()->user());

        // Use the seeder service to set up the company
        $seeder = app(CompanySeederService::class);
        $seeder->seedMinimumRequired($company);
        $seeder->seedByIndustryTemplate($company, $data['industry_type'] ?? 'generic');

        if ($seedSampleData) {
            $seeder->seedSampleData($company);
        }

        $seeder->markOnboardingComplete($company);

        return $company;
    }
}
