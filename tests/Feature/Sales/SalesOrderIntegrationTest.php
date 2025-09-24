<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use App\Actions\Sales\CreateSalesOrderAction;
use App\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use App\DataTransferObjects\Sales\CreateSalesOrderDTO;
use App\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use App\Enums\Inventory\InventoryAccountingMode;
use App\Enums\Partners\PartnerType;
use App\Enums\Products\ProductType;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Sales\SalesOrderStatus;
use App\Models\Account;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\InvoiceService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

function createCustomer()
{
    return \App\Models\Partner::factory()->create([
        'company_id' => test()->company->id,
        'type' => PartnerType::Customer,
    ]);
}

test('can create sales order with lines', function () {
    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $lineDto = new CreateSalesOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 2.0,
        unit_price: Money::of(100, $this->company->currency->code),
    );

    $dto = new CreateSalesOrderDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $this->company->currency_id,
        created_by_user_id: $this->user->id,
        so_date: Carbon::today(),
        lines: [$lineDto],
    );

    $action = app(CreateSalesOrderAction::class);
    $salesOrder = $action->execute($dto);

    expect($salesOrder)->toBeInstanceOf(SalesOrder::class);
    expect($salesOrder->status)->toBe(SalesOrderStatus::Draft);
    expect($salesOrder->lines)->toHaveCount(1);
    expect($salesOrder->total_amount->getAmount()->toInt())->toBe(200); // 2 * 100 = 200
});

test('can create invoice from sales order', function () {
    // Create a sales order first
    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
    ]);

    $lineDto = new CreateSalesOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 2.0,
        unit_price: Money::of(100, $this->company->currency->code),
    );

    $soDto = new CreateSalesOrderDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $this->company->currency_id,
        created_by_user_id: $this->user->id,
        so_date: Carbon::today(),
        lines: [$lineDto],
    );

    $createSoAction = app(CreateSalesOrderAction::class);
    $salesOrder = $createSoAction->execute($soDto);

    // Update status to confirmed so we can create invoice
    $salesOrder->update(['status' => SalesOrderStatus::Confirmed]);

    // Create invoice from sales order
    $invoiceDto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $salesOrder,
        invoice_date: Carbon::today(),
        due_date: Carbon::today()->addDays(30),
        default_income_account_id: $incomeAccount->id,
    );

    $createInvoiceAction = app(CreateInvoiceFromSalesOrderAction::class);
    $invoice = $createInvoiceAction->execute($invoiceDto);

    expect($invoice->sales_order_id)->toBe($salesOrder->id);
    expect($invoice->customer_id)->toBe($customer->id);
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
    expect($invoice->invoiceLines)->toHaveCount(1);
    expect($invoice->total_amount->getAmount()->toInt())->toBe(200); // 2 * 100 = 200
});

test('invoice posting respects inventory accounting mode', function () {
    // Test automatic mode
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL]);

    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
    ]);

    // Create invoice directly (not from sales order)
    $invoice = \App\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'sales_order_id' => null, // Direct invoice, not from sales order
    ]);

    $invoiceLine = \App\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'income_account_id' => $incomeAccount->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    $invoiceService = app(InvoiceService::class);

    // In automatic mode, stock moves should be created for direct invoices
    $stockMovesCountBefore = \App\Models\StockMove::count();
    $invoiceService->confirm($invoice, $this->user);
    $stockMovesCountAfter = \App\Models\StockMove::count();

    expect($stockMovesCountAfter)->toBeGreaterThan($stockMovesCountBefore);

    // Test manual mode
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING]);

    $invoice2 = \App\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'sales_order_id' => null, // Direct invoice, not from sales order
    ]);

    $invoiceLine2 = \App\Models\InvoiceLine::factory()->create([
        'invoice_id' => $invoice2->id,
        'product_id' => $product->id,
        'income_account_id' => $incomeAccount->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    // In manual mode, no stock moves should be created for direct invoices
    $stockMovesCountBefore = \App\Models\StockMove::count();
    $invoiceService->confirm($invoice2, $this->user);
    $stockMovesCountAfter = \App\Models\StockMove::count();

    expect($stockMovesCountAfter)->toBe($stockMovesCountBefore);
});

test('invoice from sales order does not create stock moves regardless of mode', function () {
    // Even in automatic mode, invoices from sales orders should not create stock moves
    // because the sales order handles deliveries separately
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL]);

    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
    ]);

    // Create sales order and invoice from it
    $lineDto = new CreateSalesOrderLineDTO(
        product_id: $product->id,
        description: 'Test Product',
        quantity: 1.0,
        unit_price: Money::of(100, $this->company->currency->code),
    );

    $soDto = new CreateSalesOrderDTO(
        company_id: $this->company->id,
        customer_id: $customer->id,
        currency_id: $this->company->currency_id,
        created_by_user_id: $this->user->id,
        so_date: Carbon::today(),
        lines: [$lineDto],
    );

    $createSoAction = app(CreateSalesOrderAction::class);
    $salesOrder = $createSoAction->execute($soDto);
    $salesOrder->update(['status' => SalesOrderStatus::Confirmed]);

    $invoiceDto = new CreateInvoiceFromSalesOrderDTO(
        salesOrder: $salesOrder,
        invoice_date: Carbon::today(),
        due_date: Carbon::today()->addDays(30),
        default_income_account_id: $incomeAccount->id,
    );

    $createInvoiceAction = app(CreateInvoiceFromSalesOrderAction::class);
    $invoice = $createInvoiceAction->execute($invoiceDto);

    // Post the invoice
    $stockMovesCountBefore = \App\Models\StockMove::count();
    $invoiceService = app(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);
    $stockMovesCountAfter = \App\Models\StockMove::count();

    // No stock moves should be created because invoice is linked to sales order
    expect($stockMovesCountAfter)->toBe($stockMovesCountBefore);
    expect($invoice->sales_order_id)->toBe($salesOrder->id);
});
