<?php

namespace Modules\Inventory\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Models\AdjustmentDocumentLine;
use App\Models\Company;
use App\Services\Accounting\LockDateService;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAdjustmentDocumentAction
{
    public function __construct(
        private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly CreateAdjustmentDocumentLineAction $createAdjustmentDocumentLineAction,
        private readonly \Modules\Foundation\Services\CurrencyConverterService $currencyConverter
    ) {}

    public function execute(CreateAdjustmentDocumentDTO $dto): \Modules\Inventory\Models\AdjustmentDocument
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->date));

        return DB::transaction(function () use ($dto): \Modules\Inventory\Models\AdjustmentDocument {
            $currency = \Modules\Foundation\Models\Currency::find($dto->currency_id);
            if (! $currency) {
                throw new \InvalidArgumentException('Currency not found');
            }
            $currencyCode = $currency->code;

            // Create the header first with zero totals
            $adjustmentDocument = \Modules\Inventory\Models\AdjustmentDocument::create([
                'company_id' => $dto->company_id,
                'type' => $dto->type->value,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
                'subtotal' => Money::of(0, $currencyCode),     // Initialize with 0
                'total_amount' => Money::of(0, $currencyCode), // Initialize with 0
                'total_tax' => Money::of(0, $currencyCode),    // Initialize with 0
                'status' => AdjustmentDocumentStatus::Draft,
            ]);

            // Create the lines using the dedicated line action
            foreach ($dto->lines as $lineDto) {
                $this->createAdjustmentDocumentLineAction->execute($adjustmentDocument, $lineDto);
            }

            // Refresh the model to get the latest calculated totals from the observer
            $adjustmentDocument->refresh();

            // Process multi-currency amounts after lines are created and totals calculated
            $this->processMultiCurrencyAmounts($adjustmentDocument);

            // Return the fresh model with all updates
            $fresh = $adjustmentDocument->fresh();
            if (! $fresh) {
                throw new \RuntimeException('Failed to refresh adjustment document after creation');
            }

            return $fresh;
        });
    }

    /**
     * Process multi-currency amounts for an adjustment document.
     * Captures exchange rate and converts amounts to company base currency.
     */
    protected function processMultiCurrencyAmounts(\Modules\Inventory\Models\AdjustmentDocument $adjustmentDocument): void
    {
        // Load necessary relationships
        $adjustmentDocument->load(['company', 'currency', 'lines']);

        // If adjustment document is in company base currency, set rate to 1.0
        if ($adjustmentDocument->currency_id === $adjustmentDocument->company->currency_id) {
            $adjustmentDocument->exchange_rate_at_creation = 1.0;
            $adjustmentDocument->subtotal_company_currency = $adjustmentDocument->subtotal;
            $adjustmentDocument->total_amount_company_currency = $adjustmentDocument->total_amount;
            $adjustmentDocument->total_tax_company_currency = $adjustmentDocument->total_tax;

            // Also set line-level company currency amounts (same as document currency)
            /** @var AdjustmentDocumentLine $line */
            foreach ($adjustmentDocument->lines as $line) {
                $line->update([
                    'unit_price_company_currency' => $line->unit_price,
                    'subtotal_company_currency' => $line->subtotal,
                    'total_line_tax_company_currency' => $line->total_line_tax,
                ]);
            }

            $adjustmentDocument->save();

            return;
        }

        // Get exchange rate for the adjustment document date
        $exchangeRate = $this->currencyConverter->getExchangeRate($adjustmentDocument->currency, $adjustmentDocument->date, $adjustmentDocument->company);

        // If no exchange rate is found, skip multi-currency processing for backward compatibility
        if (! $exchangeRate) {
            $adjustmentDocument->exchange_rate_at_creation = 1.0;
            $adjustmentDocument->subtotal_company_currency = $adjustmentDocument->subtotal;
            $adjustmentDocument->total_amount_company_currency = $adjustmentDocument->total_amount;
            $adjustmentDocument->total_tax_company_currency = $adjustmentDocument->total_tax;
            $adjustmentDocument->save();

            return;
        }

        // Convert amounts to company currency
        $companyCurrency = $adjustmentDocument->company->currency;

        $subtotalCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $adjustmentDocument->subtotal,
            $adjustmentDocument->currency,
            $companyCurrency,
            $adjustmentDocument->date,
            $adjustmentDocument->company
        );

        $totalAmountCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $adjustmentDocument->total_amount,
            $adjustmentDocument->currency,
            $companyCurrency,
            $adjustmentDocument->date,
            $adjustmentDocument->company
        );

        $totalTaxCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $adjustmentDocument->total_tax,
            $adjustmentDocument->currency,
            $companyCurrency,
            $adjustmentDocument->date,
            $adjustmentDocument->company
        );

        // Convert adjustment document line amounts
        /** @var AdjustmentDocumentLine $line */
        foreach ($adjustmentDocument->lines as $line) {
            $this->convertAdjustmentDocumentLineAmounts($line, $companyCurrency, $adjustmentDocument->company);
        }

        // Update adjustment document with converted amounts
        $adjustmentDocument->update([
            'exchange_rate_at_creation' => $exchangeRate,
            'subtotal_company_currency' => $subtotalCompanyCurrency,
            'total_amount_company_currency' => $totalAmountCompanyCurrency,
            'total_tax_company_currency' => $totalTaxCompanyCurrency,
        ]);
    }

    /**
     * Convert adjustment document line amounts to company currency.
     */
    protected function convertAdjustmentDocumentLineAmounts(AdjustmentDocumentLine $line, \Modules\Foundation\Models\Currency $companyCurrency, Company $company): void
    {
        /** @var \Modules\Inventory\Models\AdjustmentDocument $doc */
        $doc = $line->adjustmentDocument;

        if (! $line->unit_price) {
            throw new \InvalidArgumentException('Line unit price is required');
        }

        $unitPriceCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->unit_price,
            $doc->currency,
            $companyCurrency,
            $doc->date,
            $company
        );

        if (! $line->subtotal) {
            throw new \InvalidArgumentException('Line subtotal is required');
        }

        $subtotalCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->subtotal,
            $doc->currency,
            $companyCurrency,
            $doc->date,
            $company
        );

        if (! $line->total_line_tax) {
            throw new \InvalidArgumentException('Line total tax is required');
        }

        $totalLineTaxCompanyCurrency = $this->currencyConverter->convertToBaseCurrency(
            $line->total_line_tax,
            $doc->currency,
            $companyCurrency,
            $doc->date,
            $company
        );

        $line->update([
            'unit_price_company_currency' => $unitPriceCompanyCurrency,
            'subtotal_company_currency' => $subtotalCompanyCurrency,
            'total_line_tax_company_currency' => $totalLineTaxCompanyCurrency,
        ]);
    }
}
