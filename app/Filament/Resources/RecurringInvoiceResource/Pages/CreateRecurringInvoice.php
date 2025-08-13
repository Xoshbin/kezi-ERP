<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\DataTransferObjects\Sales\CreateRecurringInvoiceTemplateDTO;
use App\DataTransferObjects\Sales\RecurringInvoiceLineDTO;
use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Filament\Resources\RecurringInvoiceResource;
use App\Models\Currency;
use App\Services\RecurringInterCompanyService;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateRecurringInvoice extends CreateRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the company_id to the current user's company
        $data['company_id'] = Auth::user()->company_id;
        
        // Set currency_id to the company's default currency
        $data['currency_id'] = Auth::user()->company->currency_id;
        
        // Set created_by_user_id
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $currency = Currency::find($data['currency_id']);
        
        // Convert line items to DTOs
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new RecurringInvoiceLineDTO(
                description: $line['description'],
                quantity: (float) $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code),
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null,
            );
        }

        // Create the DTO
        $dto = new CreateRecurringInvoiceTemplateDTO(
            company_id: $data['company_id'],
            target_company_id: $data['target_company_id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            frequency: RecurringFrequency::from($data['frequency']),
            start_date: Carbon::parse($data['start_date']),
            end_date: $data['end_date'] ? Carbon::parse($data['end_date']) : null,
            day_of_month: $data['day_of_month'],
            month_of_quarter: $data['month_of_quarter'] ?? 1,
            currency_id: $data['currency_id'],
            income_account_id: $data['income_account_id'],
            expense_account_id: $data['expense_account_id'],
            tax_id: $data['tax_id'] ?? null,
            lines: $lineDTOs,
            created_by_user_id: $data['created_by_user_id'],
            reference_prefix: $data['reference_prefix'] ?? 'IC-RECURRING',
        );

        // Use the service to create the template
        return app(RecurringInterCompanyService::class)->createTemplate($dto);
    }
}
