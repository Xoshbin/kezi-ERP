<?php

namespace Jmeryar\Payment\Actions\LetterOfCredit;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\Foundation\Services\SequenceService;
use Jmeryar\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Jmeryar\Payment\Enums\LetterOfCredit\LCStatus;
use Jmeryar\Payment\Models\LetterOfCredit;

class CreateLetterOfCreditAction
{
    public function __construct(protected SequenceService $sequenceService) {}

    public function execute(CreateLetterOfCreditDTO $dto, User $user): LetterOfCredit
    {
        return DB::transaction(function () use ($dto) {
            // Generate LC number using SequenceService
            $lcNumber = $this->sequenceService->getNextNumber(
                company: \App\Models\Company::find($dto->company_id),
                documentType: 'letter_of_credit',
                prefix: 'LC',
                padding: 5
            );
            // Calculate initial balance (same as amount since nothing utilized yet)
            $balance = $dto->amount;

            $lc = LetterOfCredit::create([
                'company_id' => $dto->company_id,
                'vendor_id' => $dto->vendor_id,
                'issuing_bank_partner_id' => $dto->issuing_bank_partner_id,
                'currency_id' => $dto->currency_id,
                'purchase_order_id' => $dto->purchase_order_id,
                'created_by_user_id' => $dto->created_by_user_id,
                'lc_number' => $lcNumber,
                'type' => $dto->type,
                'status' => LCStatus::Draft,
                'amount' => $dto->amount,
                'amount_company_currency' => $dto->amount_company_currency,
                'utilized_amount' => $dto->amount->multipliedBy(0), // Zero with same currency
                'balance' => $balance,
                'issue_date' => $dto->issue_date,
                'expiry_date' => $dto->expiry_date,
                'shipment_date' => $dto->shipment_date,
                'incoterm' => $dto->incoterm,
                'terms_and_conditions' => $dto->terms_and_conditions,
                'notes' => $dto->notes,
            ]);

            return $lc;
        });
    }
}
