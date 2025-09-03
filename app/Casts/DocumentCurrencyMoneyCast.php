<?php

namespace App\Casts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * DocumentCurrencyMoneyCast - Uses the document's stated currency.
 *
 * This cast is used for fields that should be stored and retrieved
 * in the document's own currency, such as total_amount and total_tax
 * in VendorBill and Invoice models.
 */
class DocumentCurrencyMoneyCast extends MoneyCast
{
    /**
     * Resolve the currency from the document's header.
     */
    protected function resolveCurrency(Model $model): Currency
    {
        // Case 1: The model IS the document header (e.g., Invoice, VendorBill).
        if (isset($model->currency_id)) {
            return Currency::findOrFail($model->currency_id);
        }

        // Case 2: The model is a LINE ITEM. Find its parent document's currency.
        // This expects the parent relationship to be eager-loaded.
        if ($model->relationLoaded('invoice') && $model->invoice) {
            return $model->invoice->currency;
        }
        if ($model->relationLoaded('vendorBill') && $model->vendorBill) {
            return $model->vendorBill->currency;
        }
        if ($model->relationLoaded('adjustmentDocument') && $model->adjustmentDocument) {
            return $model->adjustmentDocument->currency;
        }
        if ($model->relationLoaded('payment') && $model->payment && $model->payment->relationLoaded('currency')) {
            return $model->payment->currency;
        }
        if ($model->relationLoaded('bankStatement') && $model->bankStatement && $model->bankStatement->relationLoaded('currency')) {
            return $model->bankStatement->currency;
        }
        // Add other parent documents here as needed

        // Fallback: If relationships are not loaded, perform database queries
        // This is less efficient but ensures the cast always works
        if (method_exists($model, 'invoice') && $model->invoice_id) {
            return $model->invoice()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'vendorBill') && $model->vendor_bill_id) {
            return $model->vendorBill()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'adjustmentDocument') && $model->adjustment_document_id) {
            return $model->adjustmentDocument()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'payment') && $model->payment_id) {
            return $model->payment()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'bankStatement') && $model->bank_statement_id) {
            return $model->bankStatement()->with('currency')->first()->currency;
        }

        throw new InvalidArgumentException('Could not resolve document currency for model '.get_class($model).'. Please ensure the model has a valid parent document relationship.');
    }
}
