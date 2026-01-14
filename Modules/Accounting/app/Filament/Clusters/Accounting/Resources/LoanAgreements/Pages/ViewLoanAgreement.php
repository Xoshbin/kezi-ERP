<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages;

use App\Models\Company;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Modules\Accounting\Actions\Loans\AccrueLoanInterestAction;
use Modules\Accounting\Actions\Loans\BuildLoanPaymentJournalEntryAction;
use Modules\Accounting\Actions\Loans\CalculateEIRAction;
use Modules\Accounting\Actions\Loans\ComputeLoanScheduleAction;
use Modules\Accounting\Actions\Loans\ReclassifyLoanCurrentPortionAction;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\LoanAgreementResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\LoanAgreement;
use Modules\Foundation\Filament\Actions\DocsAction;

class ViewLoanAgreement extends ViewRecord
{
    protected static string $resource = LoanAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('loan-agreements'),
            Actions\EditAction::make(),
            Actions\Action::make('computeSchedule')
                ->label(__('Compute Schedule'))
                ->action(function () {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
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
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    app(CalculateEIRAction::class)->execute($loan);
                    $loan->refresh();
                }),
            Actions\Action::make('accrueInterest')
                ->label(__('accounting::loan.accrue_interest'))
                ->form([
                    Select::make('journal_id')
                        ->label(__('accounting::loan.journal'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof Company) {
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
                        ->label(__('accounting::loan.interest_expense_income'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('accrued_interest_account_id')
                        ->label(__('accounting::loan.accrued_interest'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('for_month_sequence')
                        ->label(__('accounting::loan.installment'))
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof User) {
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
                ->label(__('accounting::loan.post_repayment'))
                ->form([
                    Select::make('journal_id')
                        ->label(__('accounting::loan.journal'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof Company) {
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
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('loan_account_id')
                        ->label(__('accounting::loan.loan_account'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('accrued_interest_account_id')
                        ->label(__('accounting::loan.accrued_interest'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('for_month_sequence')
                        ->label(__('accounting::loan.installment'))
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof User) {
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
                ->label(__('accounting::loan.reclassify'))
                ->form([
                    Select::make('journal_id')
                        ->label(__('accounting::loan.journal'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Journal::query();
                            if ($tenant instanceof Company) {
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
                        ->label(__('accounting::loan.long_term_account'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    Select::make('short_term_account_id')
                        ->label(__('accounting::loan.short_term_account'))
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $tenant = Filament::getTenant();
                            $q = Account::query();
                            if ($tenant instanceof Company) {
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
                        ->createOptionUsing(fn (array $data) => Account::query()->create($data)->getKey())
                        ->createOptionModalHeading(__('common.modal_title_create_account'))
                        ->createOptionAction(function ($action) {
                            return $action->modalWidth('lg');
                        })
                        ->required(),
                    TextInput::make('months')
                        ->label(__('accounting::loan.months'))
                        ->numeric()
                        ->default(12)
                        ->required(),
                    DatePicker::make('as_of_date')
                        ->label(__('accounting::loan.as_of_date'))
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $loan = $record instanceof LoanAgreement ? $record : null;
                    if (! $loan) {
                        return;
                    }
                    $user = auth()->user();
                    if (! $user instanceof User) {
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
