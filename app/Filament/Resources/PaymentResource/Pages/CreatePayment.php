<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['document_links'] = $data['document_links'] ?? [];
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $linkDTOs = [];
        foreach ($data['document_links'] as $link) {
            $linkDTOs[] = new CreatePaymentDocumentLinkDTO(
                document_type: $link['document_type'],
                document_id: $link['document_id'],
                amount_applied: $link['amount_applied']
            );
        }

        $paymentDTO = new CreatePaymentDTO(
            company_id: $data['company_id'],
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            payment_date: $data['payment_date'],
            document_links: $linkDTOs,
            reference: $data['reference']
        );

        return app(CreatePaymentAction::class)->execute($paymentDTO, Auth::user());
    }
}
