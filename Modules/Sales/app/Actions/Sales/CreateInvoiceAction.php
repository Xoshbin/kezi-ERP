<?php

namespace Modules\Sales\Actions\Sales;

use App\DataTransferObjects\Sales\CreateInvoiceDTO;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function __construct(
        private readonly \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        private readonly CreateInvoiceLineAction $createInvoiceLineAction
    ) {}

    public function execute(CreateInvoiceDTO $dto): \Modules\Sales\Models\Invoice
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->invoice_date));

        $invoice = DB::transaction(function () use ($dto) {
            $currencyCode = \Modules\Foundation\Models\Currency::findOrFail($dto->currency_id)->code;

            $invoice = \Modules\Sales\Models\Invoice::create([
                'company_id' => $dto->company_id,
                'customer_id' => $dto->customer_id,
                'currency_id' => $dto->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'payment_term_id' => $dto->payment_term_id,
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
            throw new \Exception('Failed to refresh invoice after creation');
        }

        return $freshInvoice;
    }
}
