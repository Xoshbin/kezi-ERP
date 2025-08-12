<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\Partner;
use App\Services\Reports\PartnerLedgerService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class ViewPartnerLedger extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.reports.view-partner-ledger';
    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 4;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $partnerId = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.partner_ledger');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.partner_ledger');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.partner_ledger');
    }

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('reports.filter_options'))
                    ->schema([
                        Select::make('partnerId')
                            ->label(__('reports.partner'))
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $user = Filament::auth()->user();
                                return Partner::where('company_id', $user->company_id)
                                    ->with(['receivableAccount', 'payableAccount'])
                                    ->get()
                                    ->mapWithKeys(function ($partner) {
                                        $hasAccounts = $partner->receivable_account_id && $partner->payable_account_id;
                                        $suffix = $hasAccounts ? '' : ' (⚠️ Missing Accounts)';
                                        return [$partner->id => $partner->name . $suffix];
                                    });
                            })
                            ->placeholder(__('reports.select_partner'))
                            ->helperText(__('reports.partner_accounts_required')),

                        DatePicker::make('startDate')
                            ->label(__('reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),

                        DatePicker::make('endDate')
                            ->label(__('reports.end_date'))
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
            'partnerId' => 'required|exists:partners,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $company = Company::find(Filament::auth()->user()->company_id);
        $partner = Partner::find($this->partnerId);
        $service = app(PartnerLedgerService::class);

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
            'openingBalance' => $report->openingBalance->formatTo(app()->getLocale()),
            'openingBalanceAmount' => $report->openingBalance->getAmount()->toFloat(),
            'closingBalance' => $report->closingBalance->formatTo(app()->getLocale()),
            'closingBalanceAmount' => $report->closingBalance->getAmount()->toFloat(),
            'transactionLines' => $report->transactionLines->map(fn($line) => [
                'date' => $line->date->format('Y-m-d'),
                'reference' => $line->reference,
                'transactionType' => $line->transactionType,
                'debit' => $line->debit->formatTo(app()->getLocale()),
                'debitAmount' => $line->debit->getAmount()->toFloat(),
                'credit' => $line->credit->formatTo(app()->getLocale()),
                'creditAmount' => $line->credit->getAmount()->toFloat(),
                'balance' => $line->balance->formatTo(app()->getLocale()),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
        ];
    }
}
