<?php

namespace App\Listeners\Inventory;

use App\Actions\Inventory\CreateInterCompanyStockTransferAction;
use App\Events\Inventory\StockMoveConfirmed;
use App\Services\Inventory\InterCompanyStockTransferService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateInterCompanyStockTransferListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly InterCompanyStockTransferService $interCompanyStockTransferService,
        private readonly CreateInterCompanyStockTransferAction $createInterCompanyStockTransferAction
    ) {}

    /**
     * Handle the event.
     */
    public function handle(StockMoveConfirmed $event): void
    {
        $stockMove = $event->stockMove;

        // Check if this stock move should trigger inter-company processing
        $targetCompany = $this->interCompanyStockTransferService->shouldProcessInterCompany($stockMove);

        if (!$targetCompany) {
            return;
        }

        try {
            // Create the corresponding inter-company stock transfer
            $this->createInterCompanyStockTransferAction->execute($stockMove, $targetCompany);

            Log::info("Successfully processed inter-company stock transfer for move {$stockMove->id} to company {$targetCompany->id}");
        } catch (\Exception $e) {
            Log::error("Failed to process inter-company stock transfer for move {$stockMove->id}: {$e->getMessage()}");
            
            // Re-throw the exception to trigger job retry if this is queued
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(StockMoveConfirmed $event, \Throwable $exception): void
    {
        Log::error("Inter-company stock transfer listener failed for move {$event->stockMove->id}: {$exception->getMessage()}");
    }
}
