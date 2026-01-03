<?php

namespace Modules\Sales\Observers;

use Brick\Money\Money;
use Modules\Sales\Exceptions\QuoteCannotBeModifiedException;
use Modules\Sales\Models\QuoteLine;

/**
 * Observer for QuoteLine model lifecycle events.
 *
 * Handles line calculation and enforces business rules
 * around quote line modification.
 */
class QuoteLineObserver
{
    /**
     * Handle the QuoteLine "creating" event.
     */
    public function creating(QuoteLine $line): void
    {
        $this->calculateLineAmounts($line);
    }

    /**
     * Handle the QuoteLine "updating" event.
     */
    public function updating(QuoteLine $line): void
    {
        // Check if parent quote can be modified
        $quote = $line->quote;
        if ($quote && ! $quote->isEditable()) {
            throw new QuoteCannotBeModifiedException(
                __('sales::quote.messages.modify_lines_not_editable')
            );
        }

        $this->calculateLineAmounts($line);
    }

    /**
     * Handle the QuoteLine "saved" event.
     * Update parent quote totals after line is saved.
     */
    public function saved(QuoteLine $line): void
    {
        $quote = $line->quote;
        if ($quote) {
            $quote->refresh();
            $quote->calculateTotals();
            $quote->saveQuietly();
        }
    }

    /**
     * Handle the QuoteLine "deleted" event.
     * Update parent quote totals after line is deleted.
     */
    public function deleted(QuoteLine $line): void
    {
        $quote = $line->quote;
        if ($quote) {
            $quote->refresh();
            $quote->calculateTotals();
            $quote->saveQuietly();
        }
    }

    /**
     * Calculate line amounts based on quantity, unit price, discount, and tax.
     */
    private function calculateLineAmounts(QuoteLine $line): void
    {
        $quote = $line->quote;
        if (! $quote) {
            return;
        }

        $currency = $quote->currency ?? $quote->currency()->first();
        if (! $currency) {
            return;
        }

        // Get unit price as Money object
        $unitPrice = $line->unit_price;
        if (! $unitPrice instanceof Money) {
            $unitPrice = Money::of($line->unit_price ?? 0, $currency->code);
        }

        // Calculate subtotal before discount
        $quantity = (float) ($line->quantity ?? 0);
        $grossAmount = $unitPrice->multipliedBy($quantity);

        // Calculate discount
        $discountPercentage = (float) ($line->discount_percentage ?? 0);
        $discountAmount = $grossAmount->multipliedBy($discountPercentage / 100);
        $line->discount_amount = $discountAmount;

        // Subtotal after discount
        $subtotal = $grossAmount->minus($discountAmount);
        $line->subtotal = $subtotal;

        // Calculate tax
        $taxAmount = Money::of(0, $currency->code);
        if ($line->tax_id && $line->tax) {
            $taxRate = (float) ($line->tax->rate ?? 0);
            $taxAmount = $subtotal->multipliedBy($taxRate / 100);
        }
        $line->tax_amount = $taxAmount;

        // Calculate total
        $line->total = $subtotal->plus($taxAmount);

        // Calculate company currency amounts if exchange rate is available
        $exchangeRate = $quote->exchange_rate ?? 1.0;
        $company = $quote->company ?? $quote->company()->first();
        $baseCurrencyCode = $company?->currency?->code ?? 'IQD';

        if ($currency->id !== $company?->currency_id) {
            $line->unit_price_company_currency = Money::of(
                $unitPrice->getAmount()->toFloat() * $exchangeRate,
                $baseCurrencyCode
            );
            $line->discount_amount_company_currency = Money::of(
                $discountAmount->getAmount()->toFloat() * $exchangeRate,
                $baseCurrencyCode
            );
            $line->subtotal_company_currency = Money::of(
                $subtotal->getAmount()->toFloat() * $exchangeRate,
                $baseCurrencyCode
            );
            $line->tax_amount_company_currency = Money::of(
                $taxAmount->getAmount()->toFloat() * $exchangeRate,
                $baseCurrencyCode
            );
            $line->total_company_currency = Money::of(
                $line->total->getAmount()->toFloat() * $exchangeRate,
                $baseCurrencyCode
            );
        }
    }
}
