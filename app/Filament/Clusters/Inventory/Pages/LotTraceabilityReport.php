<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Models\Lot;
use App\Models\Product;
use App\Services\Inventory\InventoryReportingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LotTraceabilityReport extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'filament.clusters.inventory.pages.lot-traceability-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?int $navigationSort = 23;

    public ?array $data = [];

    public ?array $reportData = null;

    public ?Product $selectedProduct = null;

    public ?Lot $selectedLot = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory_reports.lot_trace.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory_reports.lot_trace.title');
    }

    public function getHeading(): string
    {
        return __('inventory_reports.lot_trace.heading');
    }

    public function mount(): void
    {
        $this->form->fill([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory_reports.lot_trace.filters.title'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('inventory_reports.lot_trace.filters.product'))
                            ->options(function () {
                                return \App\Models\Product::query()
                                    ->where('company_id', \Filament\Facades\Filament::getTenant()?->getKey())
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedProduct = $state ? Product::find($state) : null;
                                $this->data['lot_id'] = null;
                                $this->selectedLot = null;
                                $this->reportData = null;
                            }),

                        Select::make('lot_id')
                            ->label(__('inventory_reports.lot_trace.filters.lot'))
                            ->options(function ($get) {
                                $productId = $get('product_id');
                                if (!$productId) {
                                    return [];
                                }

                                return Lot::where('product_id', $productId)
                                    ->where('active', true)
                                    ->pluck('lot_code', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedLot = $state ? Lot::find($state) : null;
                                $this->generateReport();
                            })
                            ->disabled(fn($get) => !$get('product_id')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        if (!$this->selectedProduct || !$this->selectedLot) {
            $this->reportData = null;
            return;
        }

        $reportingService = $this->getReportingService();

        // Generate lot trace report
        $this->reportData = $reportingService->lotTrace($this->selectedProduct, $this->selectedLot);
    }

    public function getMovementsByType(): array
    {
        if (!$this->reportData || empty($this->reportData['movements'])) {
            return [
                'incoming' => [],
                'outgoing' => [],
                'internal' => [],
            ];
        }

        $movements = collect($this->reportData['movements']);

        return [
            'incoming' => $movements->where('move_type.value', 'incoming')->values()->toArray(),
            'outgoing' => $movements->where('move_type.value', 'outgoing')->values()->toArray(),
            'internal' => $movements->where('move_type.value', 'internal')->values()->toArray(),
        ];
    }

    public function getTotalIncoming(): float
    {
        $movements = $this->getMovementsByType();
        return collect($movements['incoming'])->sum('quantity');
    }

    public function getTotalOutgoing(): float
    {
        $movements = $this->getMovementsByType();
        return collect($movements['outgoing'])->sum('quantity');
    }

    public function getTotalInternal(): float
    {
        $movements = $this->getMovementsByType();
        return collect($movements['internal'])->sum('quantity');
    }

    public function getMovementTypeColor(string $moveType): string
    {
        return match ($moveType) {
            'incoming' => 'success',
            'outgoing' => 'danger',
            'internal' => 'info',
            default => 'gray',
        };
    }

    public function getMovementTypeIcon(string $moveType): string
    {
        return match ($moveType) {
            'incoming' => 'heroicon-o-arrow-down-tray',
            'outgoing' => 'heroicon-o-arrow-up-tray',
            'internal' => 'heroicon-o-arrow-right',
            default => 'heroicon-o-minus',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label(__('inventory_reports.lot_trace.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportReport')
                ->disabled(fn() => !$this->reportData),

            \Filament\Actions\Action::make('refresh')
                ->label(__('inventory_reports.lot_trace.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport')
                ->disabled(fn() => !$this->selectedProduct || !$this->selectedLot),
        ];
    }

    public function exportReport(): void
    {
        // TODO: Implement CSV export functionality
        $this->notify('success', __('inventory_reports.lot_trace.export_started'));
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'selectedProduct' => $this->selectedProduct,
            'selectedLot' => $this->selectedLot,
            'movementsByType' => $this->getMovementsByType(),
            'totalIncoming' => $this->getTotalIncoming(),
            'totalOutgoing' => $this->getTotalOutgoing(),
            'totalInternal' => $this->getTotalInternal(),
        ];
    }
}
