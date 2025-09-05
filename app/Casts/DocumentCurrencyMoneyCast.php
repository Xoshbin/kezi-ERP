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
            $currency = Currency::findOrFail($model->currency_id);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof \Illuminate\Database\Eloquent\Collection) {
                $currency = $currency->first();
                if (!$currency) {
                    throw new InvalidArgumentException('Currency collection is empty');
                }
            }
            return $currency;
        }

        // Case 2: The model is a LINE ITEM. Find its parent document's currency.
        // This expects the parent relationship to be eager-loaded.
        if (method_exists($model, 'invoice') && $model->relationLoaded('invoice')) {
            $invoice = $model->getRelation('invoice');
            if ($invoice instanceof \Illuminate\Database\Eloquent\Model && method_exists($invoice, 'currency')) {
                $currency = $invoice->relationLoaded('currency') ? $invoice->getRelation('currency') : $invoice->currency()->first();
                if ($currency instanceof \App\Models\Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'vendorBill') && $model->relationLoaded('vendorBill')) {
            $vendorBill = $model->getRelation('vendorBill');
            if ($vendorBill instanceof \Illuminate\Database\Eloquent\Model && method_exists($vendorBill, 'currency')) {
                $currency = $vendorBill->relationLoaded('currency') ? $vendorBill->getRelation('currency') : $vendorBill->currency()->first();
                if ($currency instanceof \App\Models\Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'adjustmentDocument') && $model->relationLoaded('adjustmentDocument')) {
            $adjustmentDocument = $model->getRelation('adjustmentDocument');
            if ($adjustmentDocument instanceof \Illuminate\Database\Eloquent\Model && method_exists($adjustmentDocument, 'currency')) {
                $currency = $adjustmentDocument->relationLoaded('currency') ? $adjustmentDocument->getRelation('currency') : $adjustmentDocument->currency()->first();
                if ($currency instanceof \App\Models\Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'payment') && $model->relationLoaded('payment')) {
            $payment = $model->getRelation('payment');
            if ($payment instanceof \Illuminate\Database\Eloquent\Model && method_exists($payment, 'currency')) {
                $currency = $payment->relationLoaded('currency') ? $payment->getRelation('currency') : $payment->currency()->first();
                if ($currency instanceof \App\Models\Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'bankStatement') && $model->relationLoaded('bankStatement')) {
            $bankStatement = $model->getRelation('bankStatement');
            if ($bankStatement instanceof \Illuminate\Database\Eloquent\Model && method_exists($bankStatement, 'currency')) {
                $currency = $bankStatement->relationLoaded('currency') ? $bankStatement->getRelation('currency') : $bankStatement->currency()->first();
                if ($currency instanceof \App\Models\Currency) {
                    return $currency;
                }
            }
        }
        // Add other parent documents here as needed

        // Fallback: If relationships are not loaded, perform database queries
        // This is less efficient but ensures the cast always works
        if (method_exists($model, 'invoice') && $model->getAttribute('invoice_id')) {
            $invoice = $model->invoice()->with('currency')->first();
            return $invoice->currency ?? throw new InvalidArgumentException('Invoice currency not found');
        }
        if (method_exists($model, 'vendorBill') && $model->getAttribute('vendor_bill_id')) {
            $vendorBill = $model->vendorBill()->with('currency')->first();
            return $vendorBill->currency ?? throw new InvalidArgumentException('Vendor bill currency not found');
        }
        if (method_exists($model, 'adjustmentDocument') && $model->getAttribute('adjustment_document_id')) {
            $adj = $model->adjustmentDocument()->with('currency')->first();
            return $adj->currency ?? throw new InvalidArgumentException('Adjustment document currency not found');
        }
        if (method_exists($model, 'payment') && $model->getAttribute('payment_id')) {
            $payment = $model->payment()->with('currency')->first();
            return $payment->currency ?? throw new InvalidArgumentException('Payment currency not found');
        }
        if (method_exists($model, 'bankStatement') && $model->getAttribute('bank_statement_id')) {
            $stmt = $model->bankStatement()->with('currency')->first();
            return $stmt->currency ?? throw new InvalidArgumentException('Bank statement currency not found');
        }
        // Some models expose a direct currency() relationship (e.g., PaymentDocumentLink)
        if (method_exists($model, 'currency')) {
            $currency = $model->relationLoaded('currency') ? $model->getRelation('currency') : $model->currency()->first();
            if ($currency instanceof Currency) {
                return $currency;
            }
        }

        throw new InvalidArgumentException('Could not resolve document currency for model '.get_class($model).'. Please ensure the model has a valid parent document relationship.');
    }

    /**
     * Override set to resolve currency using incoming attributes when model FKs are not yet set.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, int|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
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
