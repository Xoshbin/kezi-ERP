<?php
namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Facades\Auth;

class RegisterCompany extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register company';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Company Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('fiscal_country')
                    ->label('Fiscal Country')
                    ->required()
                    ->default('IQ')
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('currency_id')
                    ->label('Currency')
                    ->relationship('currency', 'name')
                    ->required()
                    ->searchable()
                    ->default(1)
                    ->createOptionForm([
                        TextInput::make('code')
                            ->label('Currency Code')
                            ->required()
                            ->maxLength(3),
                        TextInput::make('name')
                            ->label('Currency Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('symbol')
                            ->label('Currency Symbol')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->required()
                            ->numeric()
                            ->default(1.0),
                    ]),
            ]);
    }

    protected function handleRegistration(array $data): Company
    {
        $company = Company::create($data);

        $company->members()->attach(Auth::user());

        return $company;
    }
}
