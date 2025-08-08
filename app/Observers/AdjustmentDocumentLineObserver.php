<?php

namespace App\Observers;

use App\Models\AdjustmentDocumentLine;

class AdjustmentDocumentLineObserver
{

    /**
     * Handle the AdjustmentDocumentLine "saved" event.
     * This is triggered on both creation and update.
     */
    public function saved(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        $this->updateParentAdjustmentDocumentTotals($adjustmentDocumentLine);
    }

    /**
     * Handle the AdjustmentDocumentLine "deleted" event.
     */
    public function deleted(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        $this->updateParentAdjustmentDocumentTotals($adjustmentDocumentLine);
    }

    /**
     * Recalculate and save the totals on the parent AdjustmentDocument.
     */
    protected function updateParentAdjustmentDocumentTotals(AdjustmentDocumentLine $adjustmentDocumentLine): void
    {
        $adjustmentDocument = $adjustmentDocumentLine->adjustmentDocument;
        if ($adjustmentDocument) {
            $adjustmentDocument->calculateTotalsFromLines();
            $adjustmentDocument->saveQuietly();
        }
    }
}
