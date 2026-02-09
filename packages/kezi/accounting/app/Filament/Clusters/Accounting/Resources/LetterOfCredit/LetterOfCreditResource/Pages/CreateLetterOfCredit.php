<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource;
use Kezi\Payment\DataTransferObjects\LetterOfCredit\CreateLetterOfCreditDTO;
use Kezi\Payment\Services\LetterOfCredit\LetterOfCreditService;

/**
 * @extends CreateRecord<\Kezi\Payment\Models\LetterOfCredit>
 */
class CreateLetterOfCredit extends CreateRecord
{
    protected static string $resource = LetterOfCreditResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $company = filament()->getTenant();
        $user = auth()->user();

        $dto = new CreateLetterOfCreditDTO(
            company_id: $company->id,
            vendor_id: $data['vendor_id'],
            issuing_bank_partner_id: $data['issuing_bank_partner_id'] ?? null,
            currency_id: $data['currency_id'],
            purchase_order_id: $data['purchase_order_id'] ?? null,
            created_by_user_id: $user->id,
            amount: \Brick\Money\Money::of($data['amount'], \Kezi\Foundation\Models\Currency::find($data['currency_id'])->code),
            amount_company_currency: app(\Kezi\Foundation\Services\CurrencyConverterService::class)->convert(
                \Brick\Money\Money::of($data['amount'], \Kezi\Foundation\Models\Currency::find($data['currency_id'])->code),
                $company->currency,
                $data['issue_date'],
                $company
            ),
            issue_date: \Illuminate\Support\Carbon::parse($data['issue_date']),
            expiry_date: \Illuminate\Support\Carbon::parse($data['expiry_date']),
            shipment_date: isset($data['shipment_date']) ? \Illuminate\Support\Carbon::parse($data['shipment_date']) : null,
            type: is_string($data['type']) ? $data['type'] : $data['type']->value,
            incoterm: $data['incoterm'] ?? null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return app(LetterOfCreditService::class)->create($dto, $user);
    }
}
