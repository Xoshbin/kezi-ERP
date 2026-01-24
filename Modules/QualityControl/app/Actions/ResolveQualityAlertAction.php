<?php

namespace Modules\QualityControl\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Actions\Inventory\ScrapAction;
use Modules\QualityControl\DataTransferObjects\ResolveQualityAlertDTO;
use Modules\QualityControl\Enums\QualityAlertStatus;
use Modules\QualityControl\Models\QualityAlert;

class ResolveQualityAlertAction
{
    public function execute(ResolveQualityAlertDTO $dto): QualityAlert
    {
        $alert = QualityAlert::findOrFail($dto->qualityAlertId);

        if ($alert->status === QualityAlertStatus::Resolved || $alert->status === QualityAlertStatus::Closed) {
            throw new Exception('Quality Alert is already resolved or closed.');
        }

        return DB::transaction(function () use ($alert, $dto) {
            $alert->update([
                'root_cause' => $dto->rootCause,
                'corrective_action' => $dto->correctiveAction,
                'preventive_action' => $dto->preventiveAction,
                'status' => QualityAlertStatus::Resolved,
                'resolved_at' => now(),
            ]);

            if ($dto->scrapItems) {
                $this->scrapItems($alert);
            }

            return $alert;
        });
    }

    private function scrapItems(QualityAlert $alert): void
    {
        // We need a source location. For QualityAlert, it might come from the QualityCheck source.
        $sourceLocationId = null;

        $qualityCheck = $alert->qualityCheck;
        if ($qualityCheck && $qualityCheck->source) {
            // If source is StockPicking
            if (method_exists($qualityCheck->source, 'location_dest_id')) {
                $sourceLocationId = $qualityCheck->source->location_dest_id;
            } elseif (isset($qualityCheck->source->source_location_id)) {
                $sourceLocationId = $qualityCheck->source->source_location_id;
            }
        }

        // If we still don't have a source location, fallback to company default stock location
        if (! $sourceLocationId) {
            $sourceLocationId = $alert->company->default_stock_location_id;
        }

        if (! $sourceLocationId) {
            throw new Exception('Could not determine source location for scrapping.');
        }

        $scrapAction = app(ScrapAction::class);
        $scrapAction->execute(
            companyId: (int) $alert->company_id,
            sourceLocationId: (int) $sourceLocationId,
            items: [
                [
                    'product_id' => (int) $alert->product_id,
                    'quantity' => 1.0,
                    'lot_id' => $alert->lot_id ? (int) $alert->lot_id : null,
                    'serial_number_id' => $alert->serial_number_id ? (int) $alert->serial_number_id : null,
                ],
            ],
            reference: 'SCRAP-'.$alert->number,
            sourceType: QualityAlert::class,
            sourceId: (int) $alert->id
        );
    }
}
