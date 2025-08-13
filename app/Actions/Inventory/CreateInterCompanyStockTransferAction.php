<?php

namespace App\Actions\Inventory;

use App\DataTransferObjects\Inventory\CreateInterCompanyTransferDTO;
use App\Models\Company;
use App\Models\StockMove;
use App\Services\Inventory\InterCompanyStockTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateInterCompanyStockTransferAction
{
    public function __construct(
        private readonly InterCompanyStockTransferService $interCompanyStockTransferService
    ) {}

    /**
     * Execute inter-company stock transfer based on a source stock move
     */
    public function execute(StockMove $sourceStockMove, Company $targetCompany): void
    {
        DB::transaction(function () use ($sourceStockMove, $targetCompany) {
            // Validate that this is not a circular transfer
            if (str_starts_with($sourceStockMove->reference ?? '', 'IC-TRANSFER-')) {
                Log::info("Skipping inter-company transfer for {$sourceStockMove->id} - already an inter-company transfer");
                return;
            }

            // Validate companies are different
            if ($sourceStockMove->company_id === $targetCompany->id) {
                Log::warning("Cannot create inter-company transfer: source and target companies are the same");
                return;
            }

            // Determine the type of transfer based on the source move type
            if ($sourceStockMove->move_type === \App\Enums\Inventory\StockMoveType::Outgoing) {
                // Source company is delivering to target company
                $this->interCompanyStockTransferService->createReceiptFromDelivery($sourceStockMove, $targetCompany);
            } elseif ($sourceStockMove->move_type === \App\Enums\Inventory\StockMoveType::Incoming) {
                // Source company is receiving from target company
                $this->interCompanyStockTransferService->createDeliveryFromReceipt($sourceStockMove, $targetCompany);
            }

            Log::info("Processed inter-company stock transfer for move {$sourceStockMove->id} to company {$targetCompany->id}");
        });
    }

    /**
     * Execute inter-company stock transfer using DTO
     */
    public function executeFromDTO(CreateInterCompanyTransferDTO $dto): void
    {
        $sourceStockMove = StockMove::findOrFail($dto->source_stock_move_id);
        $targetCompany = Company::findOrFail($dto->target_company_id);

        $this->execute($sourceStockMove, $targetCompany);
    }

    /**
     * Create a bi-directional inter-company stock transfer
     * This creates both the delivery in the source company and receipt in the target company
     */
    public function createBidirectionalTransfer(CreateInterCompanyTransferDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $sourceCompany = Company::findOrFail($dto->source_company_id);
            $targetCompany = Company::findOrFail($dto->target_company_id);

            // Validate companies are different
            if ($sourceCompany->id === $targetCompany->id) {
                throw new \InvalidArgumentException('Source and target companies must be different');
            }

            // Create the delivery move in the source company
            $deliveryMove = $this->createDeliveryMove($dto, $sourceCompany);

            // Create the receipt move in the target company
            $receiptMove = $this->createReceiptMove($dto, $targetCompany, $deliveryMove);

            Log::info("Created bidirectional inter-company transfer: delivery {$deliveryMove->id} in company {$sourceCompany->id}, receipt {$receiptMove->id} in company {$targetCompany->id}");

            return [
                'delivery' => $deliveryMove,
                'receipt' => $receiptMove,
            ];
        });
    }

    /**
     * Create delivery move in source company
     */
    private function createDeliveryMove(CreateInterCompanyTransferDTO $dto, Company $sourceCompany): StockMove
    {
        $locations = $this->getCompanyLocations($sourceCompany);

        $deliveryDTO = new \App\DataTransferObjects\Inventory\CreateStockMoveDTO(
            company_id: $sourceCompany->id,
            product_id: $dto->product_id,
            quantity: $dto->quantity,
            from_location_id: $locations['warehouse']->id,
            to_location_id: $locations['customer']->id, // Customer location represents target company
            move_type: \App\Enums\Inventory\StockMoveType::Outgoing,
            status: \App\Enums\Inventory\StockMoveStatus::Done,
            move_date: $dto->transfer_date,
            reference: $dto->reference,
            created_by_user_id: $dto->created_by_user_id,
        );

        return app(\App\Actions\Inventory\CreateStockMoveAction::class)->execute($deliveryDTO);
    }

    /**
     * Create receipt move in target company
     */
    private function createReceiptMove(CreateInterCompanyTransferDTO $dto, Company $targetCompany, StockMove $deliveryMove): StockMove
    {
        $locations = $this->getCompanyLocations($targetCompany);

        $receiptDTO = new \App\DataTransferObjects\Inventory\CreateStockMoveDTO(
            company_id: $targetCompany->id,
            product_id: $dto->product_id,
            quantity: $dto->quantity,
            from_location_id: $locations['vendor']->id, // Vendor location represents source company
            to_location_id: $locations['warehouse']->id,
            move_type: \App\Enums\Inventory\StockMoveType::Incoming,
            status: \App\Enums\Inventory\StockMoveStatus::Done,
            move_date: $dto->transfer_date,
            reference: "IC-TRANSFER-{$deliveryMove->id}",
            source_type: StockMove::class,
            source_id: $deliveryMove->id,
            created_by_user_id: $dto->created_by_user_id,
        );

        return app(\App\Actions\Inventory\CreateStockMoveAction::class)->execute($receiptDTO);
    }

    /**
     * Get appropriate stock locations for a company
     */
    private function getCompanyLocations(Company $company): array
    {
        return [
            'warehouse' => $company->defaultStockLocation,
            'vendor' => $company->vendorLocation,
            'customer' => $company->customerLocation ?? $company->vendorLocation,
        ];
    }
}
