<?php

namespace App\Actions\Sales;

use App\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action for creating a customer invoice from a sales order
 */
class CreateInvoiceFromSalesOrderAction
{
    public function __construct(
        private readonly LockDateService $lockDateService,
        private readonly CreateInvoiceLineAction $createInvoiceLineAction
    ) {}

    /**
     * Execute the action to create an invoice from a sales order
     */
    public function execute(CreateInvoiceFromSalesOrderDTO $dto): Invoice
    {
        $salesOrder = $dto->salesOrder;

        // Validate that the sales order can create an invoice
        if (!$salesOrder->canCreateInvoice()) {
            throw ValidationException::withMessages([
                'sales_order' => 'This sales order cannot be invoiced in its current status.',
            ]);
        }

        // Validate that there are no existing invoices for this sales order
        if ($salesOrder->hasInvoices()) {
            throw ValidationException::withMessages([
                'sales_order' => 'An invoice already exists for this sales order.',
            ]);
        }

        // Enforce lock date
        $this->lockDateService->enforce($salesOrder->company, $dto->invoice_date);

        return DB::transaction(function () use ($dto, $salesOrder) {
            $currencyCode = $salesOrder->currency->code;

            // Create the invoice
            $invoice = Invoice::create([
                'company_id' => $salesOrder->company_id,
                'customer_id' => $salesOrder->customer_id,
                'sales_order_id' => $salesOrder->id,
                'currency_id' => $salesOrder->currency_id,
                'fiscal_position_id' => $dto->fiscal_position_id,
                'invoice_date' => $dto->invoice_date,
                'due_date' => $dto->due_date,
                'payment_term_id' => $dto->payment_term_id,
                'status' => InvoiceStatus::Draft,
                'total_amount' => Money::of(0, $currencyCode),
                'total_tax' => Money::of(0, $currencyCode),
                'exchange_rate_at_creation' => $salesOrder->exchange_rate_at_creation,
            ]);

            // Create invoice lines from sales order lines
            foreach ($salesOrder->lines as $soLine) {
                // Only create invoice lines for quantities that haven't been invoiced yet
                $remainingQuantity = $soLine->getRemainingToInvoice();

                if ($remainingQuantity > 0) {
                    $invoiceLineDto = new CreateInvoiceLineDTO(
                        description: $soLine->description,
                        quantity: $remainingQuantity,
                        unit_price: $soLine->unit_price,
                        income_account_id: $soLine->product->income_account_id ?? $dto->default_income_account_id,
                        product_id: $soLine->product_id,
                        tax_id: $soLine->tax_id,
                    );

                    $this->createInvoiceLineAction->execute($invoice, $invoiceLineDto);

                    // Update the sales order line's invoiced quantity
                    $soLine->updateInvoicedQuantity($soLine->quantity_invoiced + $remainingQuantity);
                }
            }

            // Refresh and calculate totals
            $invoice->refresh();
            $invoice->calculateTotalsFromLines();
            $invoice->save();

            // Update sales order status based on invoicing progress
            $this->updateSalesOrderStatus($salesOrder);

            return $invoice;
        });
    }

    /**
     * Update the sales order status based on invoicing progress
     */
    private function updateSalesOrderStatus(SalesOrder $salesOrder): void
    {
        $salesOrder->refresh();

        if ($salesOrder->isFullyInvoiced()) {
            if ($salesOrder->isFullyDelivered()) {
                $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::Done;
            } else {
                $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::FullyInvoiced;
            }
        } else {
            $salesOrder->status = \App\Enums\Sales\SalesOrderStatus::PartiallyInvoiced;
        }

        $salesOrder->save();
    }
}
