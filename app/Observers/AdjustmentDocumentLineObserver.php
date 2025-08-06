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
        $currency = $line->adjustmentDocument->currency;
        $quantity = $line->quantity;

        // If unit_price is already a Money object, use it. Otherwise, create it from the numeric value.
        $unitPrice = $line->unit_price instanceof Money
            ? $line->unit_price
            : Money::of($line->unit_price, $currency->code);

        $subtotal = $unitPrice->multipliedBy($quantity);
        $line->subtotal = $subtotal;

        $totalLineTax = Money::of(0, $currency->code);
        if ($line->tax_id) {
            $tax = Tax::find($line->tax_id);
            if ($tax) {
                // Ensure the tax calculation also uses the correct currency context.
                $totalLineTax = $subtotal->multipliedBy($tax->rate);
            }
        }
        $line->total_line_tax = $totalLineTax;
    }
}
