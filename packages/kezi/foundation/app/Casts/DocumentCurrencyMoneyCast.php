<?php

namespace Kezi\Foundation\Casts;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Payment\Models\Payment;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\RequestForQuotation;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\Quote;
use Kezi\Sales\Models\SalesOrder;

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
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException('Currency collection is empty');
                }
            }

            return $currency;
        }

        // Case 2: The model is a LINE ITEM. Find its parent document's currency.
        // This expects the parent relationship to be eager-loaded.
        if (method_exists($model, 'invoice') && $model->relationLoaded('invoice')) {
            $invoice = $model->getRelation('invoice');
            if ($invoice instanceof Model && method_exists($invoice, 'currency')) {
                $currency = $invoice->relationLoaded('currency') ? $invoice->getRelation('currency') : $invoice->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'vendorBill') && $model->relationLoaded('vendorBill')) {
            $vendorBill = $model->getRelation('vendorBill');
            if ($vendorBill instanceof Model && method_exists($vendorBill, 'currency')) {
                $currency = $vendorBill->relationLoaded('currency') ? $vendorBill->getRelation('currency') : $vendorBill->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'adjustmentDocument') && $model->relationLoaded('adjustmentDocument')) {
            $adjustmentDocument = $model->getRelation('adjustmentDocument');
            if ($adjustmentDocument instanceof Model && method_exists($adjustmentDocument, 'currency')) {
                $currency = $adjustmentDocument->relationLoaded('currency') ? $adjustmentDocument->getRelation('currency') : $adjustmentDocument->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'payment') && $model->relationLoaded('payment')) {
            $payment = $model->getRelation('payment');
            if ($payment instanceof Model && method_exists($payment, 'currency')) {
                $currency = $payment->relationLoaded('currency') ? $payment->getRelation('currency') : $payment->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'bankStatement') && $model->relationLoaded('bankStatement')) {
            $bankStatement = $model->getRelation('bankStatement');
            if ($bankStatement instanceof Model && method_exists($bankStatement, 'currency')) {
                $currency = $bankStatement->relationLoaded('currency') ? $bankStatement->getRelation('currency') : $bankStatement->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'purchaseOrder') && $model->relationLoaded('purchaseOrder')) {
            $purchaseOrder = $model->getRelation('purchaseOrder');
            if ($purchaseOrder instanceof Model && method_exists($purchaseOrder, 'currency')) {
                $currency = $purchaseOrder->relationLoaded('currency') ? $purchaseOrder->getRelation('currency') : $purchaseOrder->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'salesOrder') && $model->relationLoaded('salesOrder')) {
            $salesOrder = $model->getRelation('salesOrder');
            if ($salesOrder instanceof Model && method_exists($salesOrder, 'currency')) {
                $currency = $salesOrder->relationLoaded('currency') ? $salesOrder->getRelation('currency') : $salesOrder->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'quote') && $model->relationLoaded('quote')) {
            $quote = $model->getRelation('quote');
            if ($quote instanceof Model && method_exists($quote, 'currency')) {
                $currency = $quote->relationLoaded('currency') ? $quote->getRelation('currency') : $quote->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        if (method_exists($model, 'rfq') && $model->relationLoaded('rfq')) {
            $rfq = $model->getRelation('rfq');
            if ($rfq instanceof Model && method_exists($rfq, 'currency')) {
                $currency = $rfq->relationLoaded('currency') ? $rfq->getRelation('currency') : $rfq->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }
        // Loan schedule: resolve via parent loan
        if (method_exists($model, 'loan') && $model->relationLoaded('loan')) {
            $loan = $model->getRelation('loan');
            if ($loan instanceof Model && method_exists($loan, 'currency')) {
                $currency = $loan->relationLoaded('currency') ? $loan->getRelation('currency') : $loan->currency()->first();
                if ($currency instanceof Currency) {
                    return $currency;
                }
            }
        }

        // Installment: resolve via installmentable morph relation
        if (method_exists($model, 'installmentable') && $model->relationLoaded('installmentable')) {
            $parent = $model->getRelation('installmentable');
            if ($parent instanceof Model && method_exists($parent, 'currency')) {
                $currency = $parent->relationLoaded('currency') ? $parent->getRelation('currency') : $parent->currency()->first();
                if ($currency instanceof Currency) {
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
        if (method_exists($model, 'loan') && $model->getAttribute('loan_id')) {
            $loan = $model->loan()->with('currency')->first();

            return $loan->currency ?? throw new InvalidArgumentException('Loan currency not found');
        }
        if (method_exists($model, 'purchaseOrder') && $model->getAttribute('purchase_order_id')) {
            $purchaseOrder = $model->purchaseOrder()->with('currency')->first();

            return $purchaseOrder->currency ?? throw new InvalidArgumentException('Purchase order currency not found');
        }
        if (method_exists($model, 'salesOrder') && $model->getAttribute('sales_order_id')) {
            $salesOrder = $model->salesOrder()->with('currency')->first();

            return $salesOrder->currency ?? throw new InvalidArgumentException('Sales order currency not found');
        }
        if (method_exists($model, 'quote') && $model->getAttribute('quote_id')) {
            $quote = $model->quote()->with('currency')->first();

            return $quote->currency ?? throw new InvalidArgumentException('Quote currency not found');
        }
        if (method_exists($model, 'installmentable') && $model->getAttribute('installment_id')) {
            $parent = $model->installmentable()->with('currency')->first();

            return $parent->currency ?? throw new InvalidArgumentException('Installmentable currency not found');
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
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [$key => $value->getMinorAmount()->toInt()];
        }

        if (is_numeric($value)) {
            // Try to resolve currency using raw attributes first to support factory/order of assignment
            $currency = null;
            if (isset($attributes['currency_id'])) {
                $currency = Currency::find($attributes['currency_id']);
            }
            if (! $currency && isset($attributes['invoice_id'])) {
                $currency = optional(Invoice::find($attributes['invoice_id']))->currency;
            }
            if (! $currency && isset($attributes['vendor_bill_id'])) {
                $currency = optional(VendorBill::find($attributes['vendor_bill_id']))->currency;
            }
            if (! $currency && isset($attributes['adjustment_document_id'])) {
                $currency = optional(AdjustmentDocument::find($attributes['adjustment_document_id']))->currency;
            }
            if (! $currency && isset($attributes['payment_id'])) {
                $currency = optional(Payment::find($attributes['payment_id']))->currency;
            }
            if (! $currency && isset($attributes['bank_statement_id'])) {
                $currency = optional(BankStatement::find($attributes['bank_statement_id']))->currency;
            }
            if (! $currency && isset($attributes['purchase_order_id'])) {
                $currency = optional(PurchaseOrder::find($attributes['purchase_order_id']))->currency;
            }
            if (! $currency && isset($attributes['sales_order_id'])) {
                $currency = optional(SalesOrder::find($attributes['sales_order_id']))->currency;
            }
            if (! $currency && isset($attributes['quote_id'])) {
                $currency = optional(Quote::find($attributes['quote_id']))->currency;
            }
            if (! $currency && isset($attributes['rfq_id'])) {
                $currency = optional(RequestForQuotation::find($attributes['rfq_id']))->currency;
            }

            if (! $currency) {
                $currency = $this->resolveCurrency($model);
            }

            $money = Money::of($value, $currency->code, null, \Brick\Math\RoundingMode::HALF_UP);

            return [$key => $money->getMinorAmount()->toInt()];
        }

        throw new InvalidArgumentException('Invalid value for MoneyCast: must be numeric or Money instance.');
    }
}
