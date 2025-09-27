<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Actions\Loans\AccrueLoanInterestAction;
use App\Actions\Loans\BuildLoanPaymentJournalEntryAction;
use App\Actions\Loans\CalculateEIRAction;
use App\Actions\Loans\ComputeLoanScheduleAction;
use App\Actions\Loans\ReclassifyLoanCurrentPortionAction;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use App\Models\Journal;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanAgreement extends ViewRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\App\Filament\Actions\DocsAction::make('loan-agreements'),
            Actions\EditAction::make(),
            Actions\Action::make('computeSchedule')
                ->label(__('Compute Schedule'))
                ->action(function () {
                    $record = $this->getRecord();
                    $loan = $record instanceof \Modules\Accounting\Models\LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    app(ComputeLoanScheduleAction::class)->execute($loan);
                    $loan->refresh();
                }),
            Actions\Action::make('recalculateEIR')
                ->label(__('Recalculate EIR'))
                ->action(function () {
                    $record = $this->getRecord();
                    $loan = $record instanceof \Modules\Accounting\Models\LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    app(CalculateEIRAction::class)->execute($loan);
                    $loan->refresh();
                }),
            Actions\Action::make('accrueInterest')
                ->label('Accrue Interest')
                ->form([
                    Select::make('journal_id')
                        ->label('Journal')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(JournalType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                            TextInput::make('short_code')->maxLength(16),
                        ])
                        ->createOptionUsing(fn (array $data) => Journal::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_journal'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('interest_account_id')
                        ->label('Interest Expense / Income')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('accrued_interest_account_id')
                        ->label('Accrued Interest')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('for_month_sequence')
                        ->label('Installment #')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof \Modules\Accounting\Models\LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof \App\Models\User) {
                        return;
                    }
                    app(AccrueLoanInterestAction::class)->execute(
                        loan: $loan,
                        user: $user,
                        journalId: (int) $data['journal_id'],
                        interestAccountId: (int) $data['interest_account_id'],
                        accruedInterestAccountId: (int) $data['accrued_interest_account_id'],
                        forMonthSequence: (int) $data['for_month_sequence'],
                    );
                }),
            Actions\Action::make('postRepayment')
                ->label('Post Repayment')
                ->form([
                    Select::make('journal_id')
                        ->label('Journal')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(JournalType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                            TextInput::make('short_code')->maxLength(16),
                        ])
                        ->createOptionUsing(fn (array $data) => Journal::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_journal'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('bank_account_id')
                        ->label('Bank Account')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('loan_account_id')
                        ->label('Loan Account')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('accrued_interest_account_id')
                        ->label('Accrued Interest')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('for_month_sequence')
                        ->label('Installment #')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof \Modules\Accounting\Models\LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof \App\Models\User) {
                        return;
                    }
                    app(BuildLoanPaymentJournalEntryAction::class)->execute(
                        loan: $loan,
                        user: $user,
                        journalId: (int) $data['journal_id'],
                        bankAccountId: (int) $data['bank_account_id'],
                        loanAccountId: (int) $data['loan_account_id'],
                        accruedInterestAccountId: (int) $data['accrued_interest_account_id'],
                        forMonthSequence: (int) $data['for_month_sequence'],
                    );
                }),
            Actions\Action::make('reclassifyCurrentPortion')
                ->label('Reclassify Current Portion')
                ->form([
                    Select::make('journal_id')
                        ->label('Journal')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(JournalType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                            TextInput::make('short_code')->maxLength(16),
                        ])
                        ->createOptionUsing(fn (array $data) => Journal::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_journal'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('long_term_account_id')
                        ->label('Long-term Account')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('short_term_account_id')
                        ->label('Short-term Account')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = \Modules\Accounting\Models\Account::query();
                            if ($tenant instanceof \App\Models\Company) {
                                $q->where('company_id', $tenant->getKey());
                            }

                            return $q->pluck('name', 'id');
                        })
                        ->createOptionForm([
                            Hidden::make('company_id')->default(fn () => Filament::getTenant()?->getKey()),
                            TextInput::make('code')->required(),
                            TextInput::make('name')->required(),
                            Select::make('type')->options(
                                collect(\Modules\Accounting\Enums\Accounting\AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            )->required(),
                        ])
                        ->createOptionUsing(fn (array $data) => \Modules\Accounting\Models\Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('months')
                        ->label('Months')
                        ->numeric()
                        ->default(12)
                        ->required(),
                    DatePicker::make('as_of_date')
                        ->label('As of date')
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof \Modules\Accounting\Models\LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof \App\Models\User) {
                        return;
                    }
                    app(ReclassifyLoanCurrentPortionAction::class)->execute(
                        loan: $loan,
                        user: $user,
                        journalId: (int) $data['journal_id'],
                        longTermAccountId: (int) $data['long_term_account_id'],
                        shortTermAccountId: (int) $data['short_term_account_id'],
                        months: (int) $data['months'],
                        asOfDate: (string) $data['as_of_date'],
                    );
                }),
        ];
    }
}
