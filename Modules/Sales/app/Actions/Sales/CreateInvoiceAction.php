<?php

namespace Modules\Sales\Actions\Sales;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Foundation\Models\Currency;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;

class CreateInvoiceAction
{
    public function __construct(
        private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly CreateInvoiceLineAction $createInvoiceLineAction,
    ) {}

    public function execute(CreateInvoiceDTO $dto): Invoice
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->invoice_date));

        $invoice = DB::transaction(function () use ($dto) {
            $currencyCode = Currency::findOrFail($dto->currency_id)->code;

            $invoice = Invoice::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
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
