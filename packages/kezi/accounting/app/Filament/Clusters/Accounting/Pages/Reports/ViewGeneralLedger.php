<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Models\Company;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Support\TranslatableHelper;
use Xoshbin\TranslatableSelect\Services\LocaleResolver;
use Xoshbin\TranslatableSelect\Services\TranslatableSearchService;

class ViewGeneralLedger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'accounting::filament.pages.reports.view-general-ledger';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<int, int>|null */
    public ?array $accountIds = null;

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.general_ledger');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.general_ledger');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.general_ledger');
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
                Section::make(__('accounting::reports.date_range'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('accounting::reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label(__('accounting::reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->columns(2),
                Section::make(__('accounting::reports.account_filter'))
                    ->schema([
                        Select::make('accountIds')
                            ->label(__('accounting::reports.accounts'))
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                $tenant = Filament::getTenant();

                                $searchService = app(TranslatableSearchService::class);
                                $localeResolver = app(LocaleResolver::class);
                                $searchLocales = $localeResolver->getModelLocales(Account::class);

                                $results = $searchService->getFilamentSearchResults(Account::class, $search, [
                                    'searchFields' => ['name', 'code'],
                                    'labelField' => 'name',
                                    'searchLocales' => $searchLocales,
                                    'queryModifier' => fn ($query) => $query->where('company_id', $tenant?->getKey()),
                                    'limit' => 50,
                                ]);

                                // Format results to include code
                                $formattedResults = [];
                                foreach ($results as $id => $name) {
                                    $account = Account::find($id);
                                    if ($account) {
                                        $formattedResults[$id] = $account->code.' - '.$name;
                                    }
                                }

                                return $formattedResults;
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Account::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(function (Account $account) {
                                        $accountName = TranslatableHelper::getLocalizedValue($account->name);

                                        return [$account->id => "{$account->code} - {$accountName}"];
                                    })
                                    ->toArray();
                            })
                            ->placeholder(__('accounting::reports.all_accounts'))
                            ->helperText(__('accounting::reports.account_filter_help')),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('general-ledger-report'),
            Action::make('generate')
                ->label(__('accounting::reports.generate_report'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            return;
        }
        $service = app(\Kezi\Accounting\Services\Reports\GeneralLedgerService::class);

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
                'openingBalance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($account->openingBalance),
                'closingBalance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($account->closingBalance),
                'transactionLines' => $account->transactionLines->map(fn ($line) => [
                    'journalEntryId' => $line->journalEntryId,
                    'date' => $line->date->format('Y-m-d'),
                    'reference' => $line->reference,
                    'description' => $line->description,
                    'contraAccount' => $line->contraAccount,
                    'debit' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->debit),
                    'hasDebit' => $line->debit->isPositive(),
                    'credit' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->credit),
                    'hasCredit' => $line->credit->isPositive(),
                    'balance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                ])->toArray(),
            ])->toArray(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'companyName' => $company->name,
        ];
    }
}
