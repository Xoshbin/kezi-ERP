<?php

namespace Modules\Sales\Tests\Feature\Sales;

use Carbon\Carbon;
use Brick\Money\Money;
use Modules\Sales\Models\Invoice;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\InvoiceLine;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Models\StockMove;
use Tests\Traits\WithConfiguredCompany;
use Modules\Sales\Services\InvoiceService;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Product\Enums\Products\ProductType;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Sales\Actions\Sales\CreateSalesOrderAction;
use Modules\Inventory\Enums\Inventory\InventoryAccountingMode;
use Modules\Sales\DataTransferObjects\Sales\CreateSalesOrderDTO;
use Modules\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use Modules\Sales\DataTransferObjects\Sales\CreateSalesOrderLineDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

function createCustomer()
{
    return Partner::factory()->create([
        'company_id' => test()->company->id,
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);
}

test('can create sales order with lines', function () {
    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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

test('direct invoices always create stock moves regardless of inventory mode', function () {
    // Test automatic mode
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL]);

    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
    ]);

    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
    ]);

    // Create invoice directly (not from sales order)
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'sales_order_id' => null, // Direct invoice, not from sales order
    ]);

    $invoiceLine = InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'income_account_id' => $incomeAccount->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    $invoiceService = app(InvoiceService::class);

    // In automatic mode, stock moves should be created for direct invoices
    $stockMovesCountBefore = StockMove::count();
    $invoiceService->confirm($invoice, $this->user);
    $stockMovesCountAfter = StockMove::count();

    expect($stockMovesCountAfter)->toBeGreaterThan($stockMovesCountBefore);

    // Test manual mode
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::MANUAL_INVENTORY_RECORDING]);

    $invoice2 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'currency_id' => $this->company->currency_id,
        'sales_order_id' => null, // Direct invoice, not from sales order
    ]);

    $invoiceLine2 = InvoiceLine::factory()->create([
        'invoice_id' => $invoice2->id,
        'product_id' => $product->id,
        'income_account_id' => $incomeAccount->id,
        'quantity' => 1,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    // Even in manual mode, stock moves should be created for direct invoices
    // (inventory mode only affects vendor bills, not customer invoices)
    $stockMovesCountBefore = StockMove::count();
    $invoiceService->confirm($invoice2, $this->user);
    $stockMovesCountAfter = StockMove::count();

    expect($stockMovesCountAfter)->toBeGreaterThan($stockMovesCountBefore);
});

test('invoice from sales order does not create stock moves regardless of mode', function () {
    // Even in automatic mode, invoices from sales orders should not create stock moves
    // because the sales order handles deliveries separately
    $this->company->update(['inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL]);

    $customer = createCustomer();
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
    $stockMovesCountBefore = StockMove::count();
    $invoiceService = app(InvoiceService::class);
    $invoiceService->confirm($invoice, $this->user);
    $stockMovesCountAfter = StockMove::count();

    // No stock moves should be created because invoice is linked to sales order
    expect($stockMovesCountAfter)->toBe($stockMovesCountBefore);
    expect($invoice->sales_order_id)->toBe($salesOrder->id);
});
