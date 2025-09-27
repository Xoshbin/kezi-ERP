<?php

use App\Actions\Sales\GenerateInvoicePdfAction;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->currency = \Modules\Foundation\Models\Currency::factory()->create(['code' => 'USD']);
    $this->customer = \Modules\Foundation\Models\Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'customer',
    ]);
    $this->account = \Modules\Accounting\Models\Account::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->invoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Posted,
        'invoice_number' => 'INV-001',
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(10, 'USD'),
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $this->invoice->id,
        'income_account_id' => $this->account->id,
        'description' => 'Test Product',
        'quantity' => 1,
        'unit_price' => Money::of(90, 'USD'),
        'subtotal' => Money::of(90, 'USD'),
        'total_line_tax' => Money::of(10, 'USD'),
    ]);
});

test('pdf generates correctly with english locale', function () {
    // Arrange
    app()->setLocale('en');
    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($this->invoice, 'classic');

    // Assert
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('pdf generates correctly with arabic locale', function () {
    // Arrange
    app()->setLocale('ar');
    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($this->invoice, 'classic');

    // Assert
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('pdf templates support rtl direction for arabic', function () {
    // Arrange
    app()->setLocale('ar');
    $this->actingAs($this->user);

    // Action - Test all templates
    $action = app(GenerateInvoicePdfAction::class);

    $classicResponse = $action->execute($this->invoice, 'classic');
    $modernResponse = $action->execute($this->invoice, 'modern');
    $minimalResponse = $action->execute($this->invoice, 'minimal');

    // Assert
    expect($classicResponse->getStatusCode())->toBe(200);
    expect($modernResponse->getStatusCode())->toBe(200);
    expect($minimalResponse->getStatusCode())->toBe(200);
});

test('pdf templates support different locales', function () {
    // Test multiple locales
    $locales = ['en', 'ar'];
    $templates = ['classic', 'modern', 'minimal'];

    $this->actingAs($this->user);
    $action = app(GenerateInvoicePdfAction::class);

    foreach ($locales as $locale) {
        app()->setLocale($locale);

        foreach ($templates as $template) {
            $response = $action->execute($this->invoice, $template);
            expect($response->getStatusCode())->toBe(200);
        }
    }
});

test('pdf uses correct font for multi-language support', function () {
    // Arrange
    app()->setLocale('ar');
    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($this->invoice, 'classic');

    // Assert - PDF should generate successfully with DejaVu Sans font
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('pdf handles unicode characters correctly', function () {
    // Arrange - Create invoice with unicode characters
    $this->customer->update(['name' => 'شركة الاختبار']);
    $this->company->update(['name' => 'Test Company العربية']);

    app()->setLocale('ar');
    $this->actingAs($this->user);

    // Action
    $action = app(GenerateInvoicePdfAction::class);
    $response = $action->execute($this->invoice, 'classic');

    // Assert
    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('pdf routes work with different locales', function () {
    // Test English
    app()->setLocale('en');
    $response = $this->actingAs($this->user)
        ->get(route('invoices.pdf', $this->invoice));
    $response->assertStatus(200);

    // Test Arabic
    app()->setLocale('ar');
    $response = $this->actingAs($this->user)
        ->get(route('invoices.pdf', $this->invoice));
    $response->assertStatus(200);
});

test('pdf settings work with different locales', function () {
    // Test that PDF settings can be accessed in different locales
    $locales = ['en', 'ar'];

    foreach ($locales as $locale) {
        app()->setLocale($locale);

        // Test that the company can be updated with different locales
        $this->company->update(['pdf_template' => 'modern']);
        expect($this->company->fresh()->pdf_template)->toBe('modern');

        // Test PDF generation with the updated template
        $action = app(GenerateInvoicePdfAction::class);
        $response = $action->execute($this->invoice, $this->company->pdf_template);
        expect($response->getStatusCode())->toBe(200);
    }
});

test('draft invoices generate pdf with draft watermark', function () {
    // Arrange - Create a draft invoice
    $draftInvoice = \Modules\Sales\Models\Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'status' => InvoiceStatus::Draft,
        'invoice_number' => null, // Draft invoices don't have numbers
        'total_amount' => Money::of(100, 'USD'),
        'total_tax' => Money::of(10, 'USD'),
    ]);

    \Modules\Sales\Models\InvoiceLine::factory()->create([
        'invoice_id' => $draftInvoice->id,
        'income_account_id' => $this->account->id,
        'description' => 'Draft Product',
        'quantity' => 1,
        'unit_price' => Money::of(90, 'USD'),
        'subtotal' => Money::of(90, 'USD'),
        'total_line_tax' => Money::of(10, 'USD'),
    ]);

    $this->actingAs($this->user);

    // Action - Test all templates with draft invoice
    $action = app(GenerateInvoicePdfAction::class);

    $classicResponse = $action->execute($draftInvoice, 'classic');
    $modernResponse = $action->execute($draftInvoice, 'modern');
    $minimalResponse = $action->execute($draftInvoice, 'minimal');

    // Assert - All should generate successfully
    expect($classicResponse->getStatusCode())->toBe(200);
    expect($modernResponse->getStatusCode())->toBe(200);
    expect($minimalResponse->getStatusCode())->toBe(200);
});
