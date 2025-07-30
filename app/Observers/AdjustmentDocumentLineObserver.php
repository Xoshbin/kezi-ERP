<?php
// in app/Observers/AdjustmentDocumentLineObserver.php

namespace App\Observers;

use App\Models\AdjustmentDocumentLine;
use App\Models\Tax;
use Brick\Money\Money;

class AdjustmentDocumentLineObserver
{
    public function creating(AdjustmentDocumentLine $line): void
    {
        // --- THIS IS THE FIX ---
        // Before any calculations, ensure the line has the correct currency_id from its parent.
        // This makes the currency available to the MoneyCast.
        if (is_null($line->currency_id)) {
            $line->currency_id = $line->adjustmentDocument->currency_id;
        }
        
        $this->calculateLineTotals($line);
    }

    public function updating(AdjustmentDocumentLine $line): void
    {
        $this->calculateLineTotals($line);
    }

    public function saved(AdjustmentDocumentLine $line): void
    {
        $line->adjustmentDocument->calculateTotalsFromLines();
        $line->adjustmentDocument->save();
    }

    public function deleted(AdjustmentDocumentLine $line): void
    {
        // Reload the lines relationship on the parent before recalculating
        $line->adjustmentDocument->load('lines');
        $line->adjustmentDocument->calculateTotalsFromLines();
        $line->adjustmentDocument->save();
    }

    protected function calculateLineTotals(AdjustmentDocumentLine $line): void
    {
        // Now that the line has its own currency_id, the MoneyCast will work correctly
        // when accessing unit_price here.
        $currency = $line->adjustmentDocument->currency;
        $quantity = $line->quantity;
        $unitPrice = $line->unit_price;

        $subtotal = $unitPrice->multipliedBy($quantity);
        $line->subtotal = $subtotal;

        $totalLineTax = Money::of(0, $currency->code);
        if ($line->tax_id) {
            $tax = Tax::find($line->tax_id);
            if ($tax) {
                $totalLineTax = $subtotal->multipliedBy($tax->rate);
            }
        }
        $line->total_line_tax = $totalLineTax;
    }
}