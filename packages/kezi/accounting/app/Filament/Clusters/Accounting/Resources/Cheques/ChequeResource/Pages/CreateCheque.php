<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages;

use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource;
use Kezi\Foundation\Models\Currency;
use Kezi\Payment\DataTransferObjects\Cheques\CreateChequeDTO;
use Kezi\Payment\Enums\Cheques\ChequeType;
use Kezi\Payment\Services\Cheques\ChequeService;

/**
 * @extends CreateRecord<\Kezi\Payment\Models\Cheque>
 */
class CreateCheque extends CreateRecord
{
    protected static string $resource = ChequeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Use the Service to create the record, enforcing business logic
        $service = app(ChequeService::class);
        $user = auth()->user();

        // Convert amount to Money object (assuming currency_id is present)
        $currency = Currency::findOrFail($data['currency_id']);
        $moneyAmount = Money::of($data['amount'], $currency->code);

        $dto = new CreateChequeDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id, // Get from user or context
            journal_id: $data['journal_id'],
            partner_id: $data['partner_id'],
            currency_id: $data['currency_id'],
            cheque_number: $data['cheque_number'],
            amount: $moneyAmount,
            issue_date: $data['issue_date'],
            due_date: $data['due_date'],
            type: ChequeType::from($data['type']),
            payee_name: $data['payee_name'] ?? 'Unknown', // Populate payee name from partner if not explicit? Or specific field. Cheque model has payee_name.
            // In form I didn't add payee_name text input, I relied on partner relationship.
            // Better to fetch partner name.
            chequebook_id: $data['chequebook_id'] ?? null,
            bank_name: $data['bank_name'] ?? null,
            memo: $data['memo'] ?? null
        );

        // I need to fetch Partner Name for payee_name if not provided. The DTO expects it.
        $partner = \Kezi\Foundation\Models\Partner::find($data['partner_id']);
        if ($partner) {
            // Re-instantiate DTO with correct name since property is readonly
            $dto = new CreateChequeDTO(
                company_id: \Filament\Facades\Filament::getTenant()->id,
                journal_id: $data['journal_id'],
                partner_id: $data['partner_id'],
                currency_id: $data['currency_id'],
                cheque_number: $data['cheque_number'],
                amount: $moneyAmount,
                issue_date: $data['issue_date'],
                due_date: $data['due_date'],
                type: ChequeType::from($data['type']),
                payee_name: $partner->name,
                chequebook_id: $data['chequebook_id'] ?? null,
                bank_name: $data['bank_name'] ?? null,
                memo: $data['memo'] ?? null
            );
        }

        if ($dto->type === ChequeType::Payable) {
            return $service->issue($dto, $user);
        } else {
            return $service->receive($dto, $user);
        }
    }
}
