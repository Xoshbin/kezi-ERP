<?php

namespace Modules\Sales\Actions\Sales;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Modules\Foundation\Services\SequenceService;
use Modules\Sales\DataTransferObjects\Sales\CreateQuoteDTO;
use Modules\Sales\Models\Quote;

/**
 * Action for creating a new Quote
 */
class CreateQuoteAction
{
    public function __construct(
        protected SequenceService $sequenceService,
        protected CreateQuoteLineAction $createLineAction,
    ) {}

    /**
     * Execute the action to create a quote
     */
    public function execute(CreateQuoteDTO $dto): Quote
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::findOrFail($dto->companyId);

            // Generate quote number
            $quoteNumber = $this->sequenceService->getNextNumber(
                company: $company,
                documentType: 'quote',
                prefix: 'QT',
                padding: 5
            );

            // Create the quote
            $quote = Quote::create([
                'company_id' => $dto->companyId,
                'partner_id' => $dto->partnerId,
                'currency_id' => $dto->currencyId,
                'created_by_user_id' => $dto->createdByUserId,
                'quote_number' => $quoteNumber,
                'quote_date' => $dto->quoteDate,
                'valid_until' => $dto->validUntil,
                'exchange_rate' => $dto->exchangeRate,
                'notes' => $dto->notes,
                'terms_and_conditions' => $dto->termsAndConditions,
                'subtotal' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'total' => 0,
            ]);

            // Create the quote lines
            foreach ($dto->lines as $index => $lineDto) {
                $this->createLineAction->execute($quote, $lineDto, $index);
            }

            // Refresh and recalculate totals
            $quote->refresh();
            $quote->calculateTotals();
            $quote->save();

            return $quote;
        });
    }
}
