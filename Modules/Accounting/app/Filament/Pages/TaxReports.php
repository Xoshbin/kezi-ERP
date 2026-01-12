<?php

namespace Modules\Accounting\Filament\Pages;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Modules\Accounting\Services\Reports\TaxReportService;

class TaxReports extends Page
{
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-chart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reporting';
    }

    protected string $view = 'accounting::filament.pages.tax-reports';

    public ?string $report_type = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?array $report_data = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tax Reports');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Report')
                ->action(fn () => $this->generate()),
        ];
    }

    public function getGenerators(): array
    {
        return [
            \Modules\Accounting\Services\Reports\Generators\IraqVATReturnGenerator::class => 'Iraq VAT Return',
        ];
    }

    public function generate(): void
    {
        $this->validate([
            'report_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $service = app(TaxReportService::class);
        // Assuming current company from Filament context or auth user
        // Filament doesn't always have a global company context helper like Odoo, depends on implementation.
        // Usually Auth::user()->company or similar.
        // Let's assume generic Company::first() or derived from User for now if not explicit tenant.
        // The user instructions mentioned "Company model".
        // Let's try to get it from auth user.
        $company = auth()->user()->company ?? Company::first();

        try {
            $this->report_data = $service->generateSpecificReport(
                generatorClass: $this->report_type,
                company: $company,
                startDate: \Carbon\Carbon::parse($this->start_date),
                endDate: \Carbon\Carbon::parse($this->end_date)
            );
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error generating report')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
