<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Models\Currency;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof \Illuminate\Database\Eloquent\Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new \InvalidArgumentException('Currency not found');
            }
        }

        // Prepare amount for standalone payments
        $amount = Money::of($data['amount'], $currency->code);

        $tenant = Filament::getTenant();
        $companyId = $tenant?->getKey() ?? 0;

        // For standalone payments, we need a counterpart account
        // Let's use a default account or create a simple logic
        $tenant = Filament::getTenant();
        $defaultAccount = $tenant instanceof \App\Models\Company ? $tenant->accounts()->first() : null;

        $paymentDTO = new CreatePaymentDTO(
            company_id: $companyId,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            payment_purpose: PaymentPurpose::Loan, // Use loan instead of settlement for standalone payments
            payment_type: PaymentType::from($data['payment_type']),
            payment_method: PaymentMethod::from($data['payment_method']),
            partner_id: $data['partner_id'],
            amount: $amount,
            counterpart_account_id: $defaultAccount?->id, // Use default account for non-settlement payments
            document_links: [], // No document links for standalone payments
            reference: $data['reference']
        );

        $user = Auth::user();
        if (! $user) {
            throw new \Exception('User must be authenticated to create payment');
        }

        return app(CreatePaymentAction::class)->execute($paymentDTO, $user);
    }
}
