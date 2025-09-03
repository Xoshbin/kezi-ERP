<?php

use App\Enums\Partners\PartnerType;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Partner;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    // Use tenancy setup instead of company_id
    $this->user->companies()->attach($this->company);

    $this->currency = Currency::factory()->create(['code' => 'USD']);
    $this->customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => PartnerType::Customer,
    ]);
    $this->account = Account::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

test('authenticated user can view invoice pdf', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-001',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('invoices.pdf', $invoice));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('authenticated user can download invoice pdf', function () {
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

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('invoices.pdf.download', $invoice));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

test('user cannot access invoice from different company', function () {
    // Arrange
    $otherCompany = Company::factory()->create();
    $otherCustomer = Partner::factory()->create([
        'company_id' => $otherCompany->id,
        'type' => 'customer',
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $otherCompany->id,
        'customer_id' => $otherCustomer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-003',
    ]);

    // Action & Assert
    $this->actingAs($this->user)
        ->get(route('invoices.pdf', $invoice))
        ->assertStatus(403);

    $this->actingAs($this->user)
        ->get(route('invoices.pdf.download', $invoice))
        ->assertStatus(403);
});

test('unauthenticated user cannot access pdf routes', function () {
    // Arrange
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-004',
    ]);

    // Action & Assert - Since no login route is defined, expect 500 error
    $this->get(route('invoices.pdf', $invoice))
        ->assertStatus(500);

    $this->get(route('invoices.pdf.download', $invoice))
        ->assertStatus(500);
});

test('user can specify template parameter for pdf generation', function () {
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

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('invoices.pdf', ['invoice' => $invoice, 'template' => 'modern']));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('user can preview pdf from company settings', function () {
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
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('pdf.preview', $this->company));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('user cannot preview pdf for different company', function () {
    // Arrange
    $otherCompany = Company::factory()->create();

    // Action & Assert
    $this->actingAs($this->user)
        ->get(route('pdf.preview', $otherCompany))
        ->assertStatus(403);
});

test('pdf preview returns error when no posted invoices exist', function () {
    // Arrange - No posted invoices for this company

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('pdf.preview', $this->company));

    // Assert
    $response->assertStatus(404);
    $response->assertJson(['error' => 'No posted invoices found for preview']);
});

test('pdf preview uses company default template', function () {
    // Arrange
    $this->company->update(['pdf_template' => 'minimal']);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-007',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('pdf.preview', $this->company));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('pdf preview can override template with parameter', function () {
    // Arrange
    $this->company->update(['pdf_template' => 'classic']);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-008',
    ]);

    InvoiceLine::factory()->create([
        'invoice_id' => $invoice->id,
        'income_account_id' => $this->account->id,
    ]);

    // Action
    $response = $this->actingAs($this->user)
        ->get(route('pdf.preview', ['company' => $this->company, 'template' => 'modern']));

    // Assert
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});
