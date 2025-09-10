<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas;

use App\Enums\Loans\LoanStatus;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Support\TranslatableSelect;
use App\Rules\NotInLockedPeriod;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanAgreementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('loan.form.counterparty_currency') ?: 'Counterparty & Currency')
                ->compact()
                ->schema([
                    Hidden::make('company_id')
                        ->default(function () {
                            $tenant = Filament::getTenant();

                            return $tenant instanceof \App\Models\Company ? $tenant->getKey() : null;
                        }),

                    TranslatableSelect::standard(
                        'partner_id',
                        \App\Models\Partner::class,
                        ['name', 'email', 'contact_person'],
                        __('loan.form.partner') ?: 'Partner'
                    )
                        ->columnSpanFull()
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('name')->label(__('partner.name') ?: 'Name')->required(),
                            Select::make('type')->label(__('partner.type') ?: 'Type')->options(\App\Enums\Partners\PartnerType::class)->required(),
                            TextInput::make('email')->label(__('partner.email') ?: 'Email')->email(),
                            TextInput::make('contact_person')->label(__('partner.contact_person') ?: 'Contact Person'),
                        ])
                        ->createOptionUsing(fn (array $data) => \App\Models\Partner::create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_partner') ?: 'Create Partner')
                        ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->modalWidth('lg')),

                    Group::make()
                        ->schema([
                            TextInput::make('name')
                                ->label(__('loan.form.name') ?: 'Loan Name')
                                ->maxLength(255)
                                ->columnSpanFull(),

                            ToggleButtons::make('loan_type')
                                ->label(__('loan.form.loan_type') ?: 'Loan Type')
                                ->options(collect(LoanType::cases())->mapWithKeys(fn (LoanType $t) => [$t->value => ucfirst($t->value)])->toArray())
                                ->colors([
                                    \App\Enums\Loans\LoanType::Receivable->value => 'success',
                                    \App\Enums\Loans\LoanType::Payable->value => 'danger',
                                ])
                                ->icons([
                                    'heroicon-m-arrow-down-circle' => \App\Enums\Loans\LoanType::Receivable->value,
                                    'heroicon-m-arrow-up-circle' => \App\Enums\Loans\LoanType::Payable->value,
                                ])
                                ->inline()
                                ->required()
                                ->columnSpanFull(),

                            Group::make()
                                ->schema([
                                    DatePicker::make('loan_date')
                                        ->label(__('loan.form.loan_date') ?: 'Loan Date')
                                        ->default(now())
                                        ->rules([new NotInLockedPeriod])
                                        ->required()
                                        ->columnSpan(3),
                                    DatePicker::make('start_date')
                                        ->label(__('loan.form.start_date') ?: 'Start Date')
                                        ->required()
                                        ->columnSpan(3),
                                    DatePicker::make('maturity_date')
                                        ->label(__('loan.form.maturity_date') ?: 'Maturity Date')
                                        ->columnSpan(3),
                                    TextInput::make('duration_months')
                                        ->label(__('loan.form.duration_months') ?: 'Duration (months)')
                                        ->numeric()
                                        ->suffix(__('loan.form.months') ?: 'months')
                                        ->required()
                                        ->columnSpan(3),
                                ])
                                ->columns(12)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->columnSpan(8),

                    Group::make()
                        ->schema([
                            TranslatableSelect::make('currency_id', \App\Models\Currency::class, __('loan.form.currency') ?: 'Currency')
                                ->required()
                                ->live()
                                ->default(function (): ?int {
                                    $tenant = Filament::getTenant();

                                    return $tenant instanceof \App\Models\Company ? $tenant->currency_id : null;
                                })
                                ->createOptionForm([
                                    TextInput::make('code')->label(__('currency.code') ?: 'Code')->required()->maxLength(3),
                                    TextInput::make('name')->label(__('currency.name') ?: 'Name')->required()->maxLength(255),
                                    TextInput::make('symbol')->label(__('currency.symbol') ?: 'Symbol')->maxLength(5),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_currency') ?: 'Create Currency')
                                ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->modalWidth('lg'))
                                ->columnSpanFull(),

                            MoneyInput::make('principal_amount')
                                ->label(__('loan.form.principal_amount') ?: 'Principal Amount')
                                ->currencyField('currency_id')
                                ->required()
                                ->columnSpanFull(),

                            MoneyInput::make('outstanding_principal')
                                ->label(__('loan.form.outstanding_principal') ?: 'Outstanding Principal')
                                ->currencyField('currency_id')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn (?\App\Models\LoanAgreement $record) => $record && $record->outstanding_principal)
                                ->columnSpanFull(),
                        ])
                        ->columns(12)
                        ->columnSpan(4),
                ])
                ->columns(12)
                ->columnSpanFull(),

            Section::make(__('loan.form.schedule_rates') ?: 'Schedule & Rates')
                ->compact()
                ->schema([
                    Select::make('schedule_method')
                        ->label(__('loan.form.schedule_method') ?: 'Schedule Method')
                        ->options(ScheduleMethod::class)
                        ->required()
                        ->columnSpan(4),

                    TextInput::make('interest_rate')
                        ->label(__('loan.form.interest_rate') ?: 'Nominal annual rate')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->required()
                        ->columnSpan(4),

                    Toggle::make('eir_enabled')
                        ->label(__('loan.form.eir_enabled') ?: 'Use EIR')
                        ->inline(false)
                        ->live()
                        ->columnSpan(2),

                    TextInput::make('eir_rate')
                        ->label(__('loan.form.eir_rate') ?: 'EIR periodic rate')
                        ->numeric()
                        ->suffix('%')
                        ->disabled(fn (callable $get) => ! $get('eir_enabled'))
                        ->columnSpan(2),

                    Select::make('status')
                        ->label(__('loan.form.status') ?: 'Status')
                        ->options(LoanStatus::class)
                        ->default(LoanStatus::Draft->value)
                        ->required()
                        ->columnSpan(4),
                ])
                ->columns(12)
                ->columnSpanFull(),
        ]);
    }
}
