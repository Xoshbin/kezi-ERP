<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Currency;
use Filament\Forms\Components\Toggle;
use App\Filament\Support\TranslatableSelect;
use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

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
                TextInput::make('name')
                    ->label(__('company.name'))
                    ->required()
                    ->maxLength(255),

                TranslatableSelect::make('currency_id', Currency::class, __('company.currency_id'))
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
                            ->label('Decimal Places')
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
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $company = Company::create($data);

        $company->users()->attach(auth()->user());

        return $company;
    }
}
