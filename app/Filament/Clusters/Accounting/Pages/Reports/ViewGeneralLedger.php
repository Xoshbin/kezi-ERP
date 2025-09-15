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

    /** @var array<int, int>|null */
    public ?array $accountIds = null;

    /** @var array<string, mixed>|null */
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

                                $searchService = app(\Xoshbin\TranslatableSelect\Services\TranslatableSearchService::class);
                                $localeResolver = app(\Xoshbin\TranslatableSelect\Services\LocaleResolver::class);
                                $searchLocales = $localeResolver->getModelLocales(Account::class);

                                $results = $searchService->getFilamentSearchResults(Account::class, $search, [
                                    'searchFields' => ['name', 'code'],
                                    'labelField' => 'name',
                                    'searchLocales' => $searchLocales,
                                    'queryModifier' => fn($query) => $query->where('company_id', $tenant?->getKey()),
                                    'limit' => 50,
                                ]);

                                // Format results to include code
                                $formattedResults = [];
                                foreach ($results as $id => $name) {
                                    $account = Account::find($id);
                                    if ($account) {
                                        $formattedResults[$id] = $account->code . ' - ' . $name;
                                    }
                                }

                                return $formattedResults;
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Account::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(function (Account $account) {
                                        $accountName = is_array($account->name) ? ($account->name['en'] ?? (empty($account->name) ? '' : (string) array_values($account->name)[0])) : (string) $account->name;

                                        return [$account->id => "{$account->code} - {$accountName}"];
                                    })
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
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
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
