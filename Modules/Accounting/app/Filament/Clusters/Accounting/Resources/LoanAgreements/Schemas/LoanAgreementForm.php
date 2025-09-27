<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas;

use App\Enums\Loans\LoanStatus;
use App\Enums\Loans\LoanType;
use App\Enums\Loans\ScheduleMethod;
use App\Enums\Partners\PartnerType;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRate;
use App\Models\LoanAgreement;
use App\Models\Partner;
use App\Rules\NotInLockedPeriod;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Collection;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class LoanAgreementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('loan.form.counterparty_currency') ?: 'Counterparty & Currency')
                ->compact()
                ->schema([
                    TranslatableSelect::make('partner_id')
                        ->relationship('partner', 'name')
                        ->label(__('loan.form.partner') ?: 'Partner')
                        ->searchableFields(['name', 'email', 'contact_person'])
                        ->searchable()
                        ->preload()
                        ->columnSpanFull()
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('name')->label(__('partner.name') ?: 'Name')->required(),
                            Select::make('type')->label(__('partner.type') ?: 'Type')->options(PartnerType::class)->required(),
                            TextInput::make('email')->label(__('partner.email') ?: 'Email')->email(),
                            TextInput::make('contact_person')->label(__('partner.contact_person') ?: 'Contact Person'),
                        ])
                        ->createOptionUsing(fn (array $data) => Partner::create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_partner') ?: 'Create Partner')
                        ->createOptionAction(fn (Action $action) => $action->modalWidth('lg')),

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
                                    LoanType::Receivable->value => 'success',
                                    LoanType::Payable->value => 'danger',
                                ])
                                ->icons([
                                    'heroicon-m-arrow-down-circle' => LoanType::Receivable->value,
                                    'heroicon-m-arrow-up-circle' => LoanType::Payable->value,
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
                            TranslatableSelect::forModel('currency_id', Currency::class, 'name')
                                ->label(__('invoice.currency'))
                                ->required()
                                ->live()
                                ->preload()
                                ->searchable()
                                ->default(function (): ?int {
                                    $tenant = Filament::getTenant();

                                    return $tenant instanceof Company ? $tenant->currency_id : null;
                                })
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $currency = Currency::find($state);
                                        // Ensure we have a single Currency model, not a collection
                                        if ($currency instanceof Collection) {
                                            $currency = $currency->first();
                                        }
                                        $company = Filament::getTenant();

                                        if ($currency && $company instanceof Company && $currency->id !== $company->currency_id) {
                                            // Get latest exchange rate for this company
                                            $latestRate = CurrencyRate::getLatestRate($currency->id, $company->id);
                                            if ($latestRate) {
                                                $set('current_exchange_rate', $latestRate);
                                            }
                                        } else {
                                            $set('current_exchange_rate', 1.0);
                                        }
                                    }
                                })
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
                                        ->default(1),
                                    Toggle::make('is_active')
                                        ->label(__('currency.is_active'))
                                        ->required()
                                        ->default(true),
                                ])
                                ->createOptionModalHeading(__('common.modal_title_create_currency'))
                                ->createOptionAction(function (Action $action) {
                                    return $action
                                        ->modalWidth('lg');
                                })
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
                                ->visible(fn (?LoanAgreement $record) => $record && $record->outstanding_principal)
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
