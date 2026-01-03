<?php

namespace Modules\Purchase\Actions\Purchases;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Modules\Foundation\Services\SequenceService;
use Modules\Purchase\DataTransferObjects\Purchases\CreateRFQDTO;
use Modules\Purchase\Models\RequestForQuotation;

class CreateRequestForQuotationAction
{
    public function __construct(
        protected SequenceService $sequenceService,
        protected CreateRequestForQuotationLineAction $createLineAction,
    ) {}

    public function execute(CreateRFQDTO $dto): RequestForQuotation
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::findOrFail($dto->companyId);

            $rfqNumber = $this->sequenceService->getNextRFQNumber($company);

            $rfq = RequestForQuotation::create([
                'company_id' => $dto->companyId,
                'vendor_id' => $dto->vendorId,
                'currency_id' => $dto->currencyId,
                'created_by_user_id' => $dto->createdByUserId,
                'rfq_number' => $rfqNumber,
                'rfq_date' => $dto->rfqDate,
                'valid_until' => $dto->validUntil,
                'notes' => $dto->notes,
                'exchange_rate' => $dto->exchangeRate,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            $rfq->load('currency');

            foreach ($dto->lines as $lineDto) {
                $this->createLineAction->execute($rfq, $lineDto);
            }

            $rfq->refresh();
            $rfq->calculateTotals();
            $rfq->save();

            \Modules\Purchase\Events\RequestForQuotationCreated::dispatch($rfq);

            return $rfq;
        });
    }
}
