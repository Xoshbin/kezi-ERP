<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Models\Company;
use BackedEnum;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Foundation\Models\Partner;

class ViewPartnerLedger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'accounting::filament.pages.reports.view-partner-ledger';

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

    public ?int $partnerId = null;

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.partner_ledger');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.partner_ledger');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.partner_ledger');
    }

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.filter_options'))
                    ->schema([
                        Select::make('partnerId')
                            ->label(__('accounting::reports.partner'))
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $user = Filament::auth()->user();
                                if (! $user) {
                                    return [];
                                }

                                return Partner::where('company_id', $user->company_id)
                                    ->with(['receivableAccount', 'payableAccount'])
                                    ->get()
                                    ->mapWithKeys(function ($partner) {
                                        $hasAccounts = $partner->receivable_account_id && $partner->payable_account_id;
                                        $suffix = $hasAccounts ? '' : ' (⚠️ Missing Accounts)';

                                        return [$partner->id => $partner->name.$suffix];
                                    });
                            })
                            ->placeholder(__('accounting::reports.select_partner'))
                            ->helperText(__('accounting::reports.partner_accounts_required')),

                        DatePicker::make('startDate')
                            ->label(__('accounting::reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),

                        DatePicker::make('endDate')
                            ->label(__('accounting::reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth())
                            ->afterOrEqual('startDate'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('partner-ledger-report'),
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
            'partnerId' => ['required', 'exists:partners,id'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $user = Filament::auth()->user();
        if (! $user) {
            throw new Exception('User must be authenticated to view partner ledger');
        }

        $company = Company::findOrFail($user->company_id);
        $partner = Partner::findOrFail($this->partnerId);
        $service = app(\Kezi\Accounting\Services\Reports\PartnerLedgerService::class);

        $report = $service->generate(
            $company,
            $partner,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'partnerId' => $report->partnerId,
            'partnerName' => $report->partnerName,
            'currency' => $report->currency,
            'openingBalance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->openingBalance),
            'isOpeningNegative' => $report->openingBalance->isNegative(),
            'closingBalance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->closingBalance),
            'isClosingNegative' => $report->closingBalance->isNegative(),
            'isClosingPositive' => $report->closingBalance->isPositive(),
            'transactionLines' => $report->transactionLines->map(fn ($line) => [
                'date' => $line->date->format('Y-m-d'),
                'reference' => $line->reference,
                'transactionType' => $line->transactionType,
                'debit' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->debit),
                'hasDebit' => $line->debit->isPositive(),
                'credit' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->credit),
                'hasCredit' => $line->credit->isPositive(),
                'balance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'isBalanceNegative' => $line->balance->isNegative(),
            ])->toArray(),
        ];
    }
}
