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
        if (method_exists($model, 'invoice') && $model->relationLoaded('invoice') && $model->invoice) {
            return $model->invoice->currency;
        }
        if (method_exists($model, 'vendorBill') && $model->relationLoaded('vendorBill') && $model->vendorBill) {
            return $model->vendorBill->currency;
        }
        if (method_exists($model, 'adjustmentDocument') && $model->relationLoaded('adjustmentDocument') && $model->adjustmentDocument) {
            return $model->adjustmentDocument->currency;
        }
        if (method_exists($model, 'payment') && $model->relationLoaded('payment') && $model->payment && $model->payment->relationLoaded('currency')) {
            return $model->payment->currency;
        }
        if (method_exists($model, 'bankStatement') && $model->relationLoaded('bankStatement') && $model->bankStatement && $model->bankStatement->relationLoaded('currency')) {
            return $model->bankStatement->currency;
        }
        // Add other parent documents here as needed

        // Fallback: If relationships are not loaded, perform database queries
        // This is less efficient but ensures the cast always works
        if (method_exists($model, 'invoice') && $model->getAttribute('invoice_id')) {
            return $model->invoice()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'vendorBill') && $model->getAttribute('vendor_bill_id')) {
            return $model->vendorBill()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'adjustmentDocument') && $model->getAttribute('adjustment_document_id')) {
            return $model->adjustmentDocument()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'payment') && $model->getAttribute('payment_id')) {
            return $model->payment()->with('currency')->first()->currency;
        }
        if (method_exists($model, 'bankStatement') && $model->getAttribute('bank_statement_id')) {
            return $model->bankStatement()->with('currency')->first()->currency;
        }
        // Some models expose a direct currency() relationship (e.g., PaymentDocumentLink)
        if (method_exists($model, 'currency')) {
            $currency = $model->relationLoaded('currency') ? $model->currency : $model->currency()->first();
            if ($currency instanceof Currency) {
                return $currency;
            }
        }

        throw new InvalidArgumentException('Could not resolve document currency for model '.get_class($model).'. Please ensure the model has a valid parent document relationship.');
    }

    /**
     * Override set to resolve currency using incoming attributes when model FKs are not yet set.
     */
    public function set($model, string $key, $value, array $attributes): ?array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof \Brick\Money\Money) {
            return [$key => $value->getMinorAmount()->toInt()];
        }

        if (is_numeric($value)) {
            // Try to resolve currency using raw attributes first to support factory/order of assignment
            $currency = null;
            if (isset($attributes['currency_id'])) {
                $currency = \App\Models\Currency::find($attributes['currency_id']);
            }
            if (! $currency && isset($attributes['invoice_id'])) {
                $currency = optional(\App\Models\Invoice::find($attributes['invoice_id']))->currency;
            }
            if (! $currency && isset($attributes['vendor_bill_id'])) {
                $currency = optional(\App\Models\VendorBill::find($attributes['vendor_bill_id']))->currency;
            }
            if (! $currency && isset($attributes['adjustment_document_id'])) {
                $currency = optional(\App\Models\AdjustmentDocument::find($attributes['adjustment_document_id']))->currency;
            }
            if (! $currency && isset($attributes['payment_id'])) {
                $currency = optional(\App\Models\Payment::find($attributes['payment_id']))->currency;
            }
            if (! $currency && isset($attributes['bank_statement_id'])) {
                $currency = optional(\App\Models\BankStatement::find($attributes['bank_statement_id']))->currency;
            }

            if (! $currency) {
                $currency = $this->resolveCurrency($model);
            }

            $money = \Brick\Money\Money::of($value, $currency->code);

            return [$key => $money->getMinorAmount()->toInt()];
        }

        throw new InvalidArgumentException('Invalid value for MoneyCast: must be numeric or Money instance.');
    }
}
