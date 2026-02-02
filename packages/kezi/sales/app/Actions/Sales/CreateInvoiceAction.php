<?php

namespace Kezi\Sales\Actions\Sales;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Models\Currency;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;

class CreateInvoiceAction
{
    public function __construct(
        private readonly \Kezi\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly CreateInvoiceLineAction $createInvoiceLineAction,
        private readonly \Kezi\Accounting\Services\Accounting\FiscalPositionService $fiscalPositionService,
    ) {}

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->invoice_date));

        $invoice = DB::transaction(function () use ($dto) {
            $currencyCode = Currency::findOrFail($dto->currency_id)->code;

            $fiscalPositionId = $dto->fiscal_position_id;
            if (! $fiscalPositionId) {
                $customer = \Kezi\Foundation\Models\Partner::findOrFail($dto->customer_id);
                $fiscalPosition = $this->fiscalPositionService->getFiscalPositionForPartner($customer);
                $fiscalPositionId = $fiscalPosition?->id;
            }

            $invoice = Invoice::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $fiscalPositionId,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'payment_term_id' => $dto->payment_term_id,
                'incoterm' => $dto->incoterm,
                'status' => InvoiceStatus::Draft,
                'total_amount' => Money::of(0, $currencyCode),
                'total_tax' => Money::of(0, $currencyCode),
            ]);

            foreach ($dto->lines as $lineDto) {
                $this->createInvoiceLineAction->execute($invoice, $lineDto);
            }

            return $invoice;
        });

        $freshInvoice = $invoice->fresh();
        if (! $freshInvoice) {
            throw new Exception('Failed to refresh invoice after creation');
        }

        return $freshInvoice;
    }
}
