<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas;

use App\Enums\Loans\LoanStatus;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LoanAgreementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                    ->default(function () {
                        $tenant = Filament::getTenant();
                        return $tenant instanceof \App\Models\Company ? $tenant->getKey() : null;
                    }),
                // company_id removed: tenancy provides context
                TranslatableSelect::standard('partner_id', \App\Models\Partner::class, ['name', 'email', 'contact_person'])
                    ->createOptionForm([
                        Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                        TextInput::make('name')->required(),
                        Select::make('type')->options(\App\Enums\Partners\PartnerType::class)->required(),
                        TextInput::make('email')->email(),
                        TextInput::make('contact_person'),
                    ])
                    ->createOptionUsing(fn (array $data) => \App\Models\Partner::create($data)->getKey()),
                TextInput::make('name'),
                DatePicker::make('loan_date')->required(),
                DatePicker::make('start_date')->required(),
                DatePicker::make('maturity_date'),
                TextInput::make('duration_months')->required()->numeric(),
                TranslatableSelect::standard('currency_id', \App\Models\Currency::class, ['name', 'code'])
                    ->createOptionForm([
                        TextInput::make('code')->required()->length(3),
                        TextInput::make('name')->required(),
                        TextInput::make('symbol')->maxLength(4),
                    ])
                    ->createOptionUsing(fn (array $data) => \App\Models\Currency::create($data)->getKey())
                    ->required(),
                MoneyInput::make('principal_amount')
                    ->label(__('Principal'))
                    ->currencyField('currency_id')
                    ->required(),
                MoneyInput::make('outstanding_principal')
                    ->label(__('Outstanding Principal'))
                    ->currencyField('currency_id')
                    ->default(0),
                Select::make('loan_type')
                    ->options(LoanType::class)
                    ->required(),
                Select::make('status')
                    ->options(LoanStatus::class)
                    ->default('draft')
                    ->required(),
                Select::make('schedule_method')
                    ->options(ScheduleMethod::class)
                    ->required(),
                TextInput::make('interest_rate')
                    ->label(__('Nominal annual rate %'))
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('eir_enabled')->label(__('Use EIR')),
                TextInput::make('eir_rate')
                    ->label(__('EIR periodic rate'))
                    ->numeric()
                    ->disabled(),
            ]);
    }
}
