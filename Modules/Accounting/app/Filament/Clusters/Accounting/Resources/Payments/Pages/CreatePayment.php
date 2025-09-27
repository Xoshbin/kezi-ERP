<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\App\Filament\Actions\DocsAction::make('payments'),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $currency = \Modules\Foundation\Models\Currency::findOrFail($data['currency_id']);
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

        $paymentDTO = new CreatePaymentDTO(
            company_id: $companyId,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            payment_type: PaymentType::from($data['payment_type']),
            payment_method: PaymentMethod::from($data['payment_method']),
            paid_to_from_partner_id: $data['paid_to_from_partner_id'],
            amount: $amount,
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
