<?php

namespace Modules\Sales\Tests\Feature\Actions;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Actions\Sales\CreateInvoiceAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
});

it('can create invoice successfully', function () {
    $invoiceDate = Carbon::today()->toDateString();
    $dueDate = Carbon::today()->addDays(30)->toDateString();

    $incomeAccount = \Modules\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    $lineDto = new CreateInvoiceLineDTO(
        description: 'Test Product',
        quantity: 1,
        unit_price: Money::of(100, 'USD'),
        income_account_id: $incomeAccount->id,
        product_id: \Modules\Product\Models\Product::factory()->create(['company_id' => $this->company->id])->id,
        tax_id: null,
    );

    $dto = new CreateInvoiceDTO(
        company_id: $this->company->id,
        customer_id: $this->customer->id,
        currency_id: $this->currency->id,
        invoice_date: $invoiceDate,
        due_date: $dueDate,
        lines: [$lineDto],
        fiscal_position_id: null,
        payment_term_id: null,
        incoterm: null
    );

    $action = app(CreateInvoiceAction::class);
    $invoice = $action->execute($dto);

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    expect($invoice->invoiceLines)->toHaveCount(1);
    expect($invoice->invoiceLines->first()->unit_price->getAmount()->toInt())->toBe(100);
});

it('fails to create invoice with locked date', function () {
    $this->mock(LockDateService::class, function ($mock) {
        $mock->shouldReceive('enforce')->once()->andThrow(new \Exception('Date is locked'));
    });

    $invoiceDate = Carbon::yesterday()->toDateString();
    $dto = new CreateInvoiceDTO(
        company_id: $this->company->id,
        customer_id: $this->customer->id,
        currency_id: $this->currency->id,
        invoice_date: $invoiceDate,
        due_date: $invoiceDate,
        lines: [],
        fiscal_position_id: null
    );

    $action = app(CreateInvoiceAction::class);
    $action->execute($dto);
})->throws(\Exception::class, 'Date is locked');
