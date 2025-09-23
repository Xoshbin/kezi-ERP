<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockMove extends CreateRecord
{
    protected static string $resource = StockMoveResource::class;

    public function getTitle(): string
    {
        return __('stock_move.create_title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var \App\Models\Company|null $tenant */
        $tenant = Filament::getTenant();
        if (! $tenant) {
            throw new \RuntimeException('Company context is required to create a stock move.');
        }

        // Convert product lines data to DTOs
        $productLineDTOs = [];
        foreach ($data['productLines'] ?? [] as $lineData) {
            $productLineDTOs[] = new CreateStockMoveProductLineDTO(
                product_id: $lineData['product_id'],
                quantity: $lineData['quantity'],
                from_location_id: $lineData['from_location_id'],
                to_location_id: $lineData['to_location_id'],
                description: $lineData['description'] ?? null,
                source_type: $lineData['source_type'] ?? null,
                source_id: isset($lineData['source_id']) ? (int) $lineData['source_id'] : null,
            );
        }

        $dto = new CreateStockMoveDTO(
            company_id: (int) $tenant->getKey(),
            move_type: StockMoveType::from($data['move_type']),
            status: StockMoveStatus::from($data['status']),
            move_date: Carbon::parse($data['move_date']),
            created_by_user_id: (int) Filament::auth()->id(),
            product_lines: $productLineDTOs,
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
            source_type: $data['source_type'] ?? null,
            source_id: isset($data['source_id']) ? (int) $data['source_id'] : null,
        );

        return app(CreateStockMoveAction::class)->execute($dto);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('stock-management'),
        ];
    }
}
