<?php

namespace App\Filament\Clusters\Accounting\Pages\Reports;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Models\Account;
use App\Services\Reports\GeneralLedgerService;
use App\Support\NumberFormatter;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewGeneralLedger extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.reports.view-general-ledger';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?array $accountIds = null;

    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.general_ledger');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.general_ledger');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.general_ledger');
    }

    public function mount(): void
    {
        // Set default date range to current month
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('reports.date_range'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label(__('reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->columns(2),
                Section::make(__('reports.account_filter'))
                    ->schema([
                        Select::make('accountIds')
                            ->label(__('reports.accounts'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                $tenant = Filament::getTenant();

                                return Account::searchTranslatable($search)
                                    ->where('company_id', method_exists($tenant, 'getKey') ? $tenant->getKey() : null)
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($account) => [$account->id => $account->code.' - '.$account->getTranslatedLabel('name')])
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Account::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn (Account $account) => [$account->id => "{$account->code} - {$account->name}"])
                                    ->toArray();
                            })
                            ->placeholder(__('reports.all_accounts'))
                            ->helperText(__('reports.account_filter_help')),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label(__('reports.generate_report'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof \App\Models\Company) {
            return;
        }
        $service = app(GeneralLedgerService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->accountIds
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'accounts' => $report->accounts->map(fn ($account) => [
                'accountId' => $account->accountId,
                'accountCode' => $account->accountCode,
                'accountName' => $account->accountName,
                'openingBalance' => NumberFormatter::formatMoneyTo($account->openingBalance),
                'openingBalanceAmount' => $account->openingBalance->getAmount()->toFloat(),
                'closingBalance' => NumberFormatter::formatMoneyTo($account->closingBalance),
                'closingBalanceAmount' => $account->closingBalance->getAmount()->toFloat(),
                'transactionLines' => $account->transactionLines->map(fn ($line) => [
                    'journalEntryId' => $line->journalEntryId,
                    'date' => $line->date->format('Y-m-d'),
                    'reference' => $line->reference,
                    'description' => $line->description,
                    'contraAccount' => $line->contraAccount,
                    'debit' => NumberFormatter::formatMoneyTo($line->debit),
                    'debitAmount' => $line->debit->getAmount()->toFloat(),
                    'credit' => NumberFormatter::formatMoneyTo($line->credit),
                    'creditAmount' => $line->credit->getAmount()->toFloat(),
                    'balance' => NumberFormatter::formatMoneyTo($line->balance),
                    'balanceAmount' => $line->balance->getAmount()->toFloat(),
                ])->toArray(),
            ])->toArray(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'companyName' => $company->name,
        ];
    }
}
