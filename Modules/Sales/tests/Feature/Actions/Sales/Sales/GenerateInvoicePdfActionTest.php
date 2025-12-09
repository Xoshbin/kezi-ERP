<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Company;
use Illuminate\Http\Response;
use Modules\Sales\Models\Invoice;
use Modules\Product\Models\Product;

use Modules\Sales\Models\InvoiceLine;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Models\Currency;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Actions\Sales\GenerateInvoicePdfAction;

use Illuminate\Foundation\Testing\RefreshDatabase;



beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

test('it successfully generates a pdf for a posted invoice', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-001',
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(10, 'USD'),
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'income_account_id' => $this->account->id,
        'description' => 'Test Product',
        'quantity' => 1,
        'unit_price' => Money::of(90, 'USD'),
        'subtotal' => Money::of(90, 'USD'),
        'total_line_tax' => Money::of(10, 'USD'),
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'classic');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain("invoice-{$invoice->invoice_number}.pdf");
});

test('it successfully generates pdf for a draft invoice', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
        'invoice_number' => null, // Draft invoices don't have numbers
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(10, 'USD'),
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
        'description' => 'Test Product',
        'quantity' => 1,
        'unit_price' => Money::of(90, 'USD'),
        'subtotal' => Money::of(90, 'USD'),
        'total_line_tax' => Money::of(10, 'USD'),
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'classic');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('it falls back to classic template when invalid template is provided', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-002',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'invalid-template');

    // Assert - Should not throw error and generate PDF with classic template
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
});

test('it successfully generates pdf with modern template', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-003',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'modern');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
});

test('it successfully generates pdf with minimal template', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-004',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'minimal');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
});

test('it successfully downloads pdf instead of streaming', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-005',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->download($invoice, 'classic');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain("invoice-{$invoice->invoice_number}.pdf");
});

test('it returns available templates', function () {
    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $templates = $action->getAvailableTemplates();

    // Assert
    expect($templates)->toBeArray();
    expect($templates)->toHaveKey('classic');
    expect($templates)->toHaveKey('modern');
    expect($templates)->toHaveKey('minimal');
    expect($templates['classic'])->toBe('Classic Template');
    expect($templates['modern'])->toBe('Modern Template');
    expect($templates['minimal'])->toBe('Minimal Template');
});

test('it loads all necessary relationships for pdf generation', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-006',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'income_account_id' => $this->account->id,
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($invoice, 'classic');

    // Assert - Check that relationships are loaded
    expect($invoice->relationLoaded('company'))->toBeTrue();
    expect($invoice->relationLoaded('customer'))->toBeTrue();
    expect($invoice->relationLoaded('invoiceLines'))->toBeTrue();
    expect($invoice->relationLoaded('currency'))->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
});

test('it successfully downloads pdf for a draft invoice', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(10, 'USD'),
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
        'description' => 'Test Product',
        'quantity' => 1,
        'unit_price' => Money::of(90, 'USD'),
        'subtotal' => Money::of(90, 'USD'),
        'total_line_tax' => Money::of(10, 'USD'),
    ]);

    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->download($invoice, 'classic');

    // Assert
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});
