<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Models\Currency;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $currency = Currency::find($data['currency_id']);

        // Prepare amount for standalone payments
        $amount = Money::of($data['amount'], $currency->code);

        $paymentDTO = new CreatePaymentDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            payment_purpose: PaymentPurpose::from($data['payment_purpose']),
            payment_type: PaymentType::from($data['payment_type']),
            partner_id: $data['partner_id'],
            amount: $amount,
            counterpart_account_id: $data['counterpart_account_id'],
            document_links: [], // No document links for standalone payments
            reference: $data['reference']
        );

        return app(CreatePaymentAction::class)->execute($paymentDTO, Auth::user());
    }
}
