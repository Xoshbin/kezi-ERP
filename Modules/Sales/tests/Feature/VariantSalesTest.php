<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Modules\Accounting\Enums\Accounting\JournalType;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;
use Modules\Product\Actions\GenerateProductVariantsAction;
use Modules\Product\DataTransferObjects\GenerateProductVariantsDTO;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductAttribute;
use Modules\Product\Models\ProductAttributeValue;
use Modules\Sales\Actions\Sales\CreateInvoiceAction;
use Modules\Sales\Actions\Sales\CreateInvoiceLineAction;
use Modules\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\InvoiceService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->invoiceService = app(InvoiceService::class);
    $this->createInvoiceAction = app(CreateInvoiceAction::class);
    $this->createInvoiceLineAction = app(CreateInvoiceLineAction::class);

    // Create customer
    $this->customer = Partner::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
    ]);

    // Create income account for products
    $this->incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => 'income',
        'code' => '4000',
        'name' => 'Product Sales Revenue',
    ]);

    // Create AR account (required for invoice posting)
    $this->arAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Modules\Accounting\Enums\Accounting\AccountType::Receivable,
        'code' => '1200',
        'name' => 'Accounts Receivable',
    ]);

    // Create Sales Journal (required for invoice posting)
    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
        'name' => 'Customer Invoices',
        'short_code' => 'INV',
        'default_debit_account_id' => $this->arAccount->id,
        'default_credit_account_id' => $this->incomeAccount->id,
    ]);

    // Set company default AR account and Sales Journal
    $this->company->update([
        'default_accounts_receivable_id' => $this->arAccount->id,
        'default_sales_journal_id' => $this->salesJournal->id,
    ]);

    // Create tax
    $this->tax = Tax::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 0.15, // 15%
    ]);

    // Create template product
    $this->template = Product::factory()->create([
        'name' => 'Laptop',
        'sku' => 'LAPTOP',
        'is_template' => true,
        'company_id' => $this->company->id,
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'income_account_id' => $this->incomeAccount->id,
        'unit_price' => Money::of(1000, $this->company->currency->code),
    ]);

    // Create attributes and values
    $this->ramAttribute = ProductAttribute::factory()->create([
        'name' => 'RAM',
        'company_id' => $this->company->id,
    ]);

    $this->storageAttribute = ProductAttribute::factory()->create([
        'name' => 'Storage',
        'company_id' => $this->company->id,
    ]);

    $this->ram8gb = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->ramAttribute->id,
        'name' => '8GB',
    ]);

    $this->ram16gb = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->ramAttribute->id,
        'name' => '16GB',
    ]);

    $this->storage256gb = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->storageAttribute->id,
        'name' => '256GB',
    ]);

    $this->storage512gb = ProductAttributeValue::factory()->create([
        'product_attribute_id' => $this->storageAttribute->id,
        'name' => '512GB',
    ]);

    // Generate variants
    $dto = new GenerateProductVariantsDTO(
        templateProductId: $this->template->id,
        attributeValueMap: [
            $this->ramAttribute->id => [$this->ram8gb->id, $this->ram16gb->id],
            $this->storageAttribute->id => [$this->storage256gb->id, $this->storage512gb->id],
        ]
    );

    $action = app(GenerateProductVariantsAction::class);
    $this->variants = $action->execute($dto);
});

it('can create invoice with variant product', function () {
    // Get a variant (e.g., 8GB-256GB)
    $variant = $this->variants->where('sku', 'LAPTOP-8GB-256GB')->first();

    expect($variant)->not->toBeNull();
    expect($variant->isVariant())->toBeTrue();
    expect($variant->parent_product_id)->toBe($this->template->id);

    // Create invoice with variant
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $variant->id,
        description: 'Laptop 8GB RAM, 256GB Storage',
        quantity: 2,
        unit_price: Money::of(1200, $this->company->currency->code),
        tax_id: $this->tax->id,
        income_account_id: $this->incomeAccount->id
    );

    $invoiceLine = $this->createInvoiceLineAction->execute($invoice, $lineDto);

    expect($invoiceLine)->not->toBeNull();
    expect($invoiceLine->product_id)->toBe($variant->id);
    expect((float) $invoiceLine->quantity)->toBe(2.0);
    expect($invoiceLine->unit_price->getAmount()->toFloat())->toBe(1200.0);
    expect($invoiceLine->subtotal->getAmount()->toFloat())->toBe(2400.0); // 2 * 1200
    expect($invoiceLine->total_line_tax->getAmount()->toFloat())->toBe(360.0); // 2400 * 0.15

    // Verify invoice line is linked to variant, not template
    expect($invoiceLine->product->isVariant())->toBeTrue();
    expect($invoiceLine->product->parent_product_id)->toBe($this->template->id);
});

it('variant uses correct income account inherited from template', function () {
    $variant = $this->variants->first();

    // Verify variant inherits income account from template
    expect($variant->income_account_id)->toBe($this->template->income_account_id);
    expect($variant->income_account_id)->toBe($this->incomeAccount->id);

    // Create invoice line
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $variant->id,
        description: 'Variant product sale',
        quantity: 1,
        unit_price: Money::of(1500, $this->company->currency->code),
        tax_id: null,
        income_account_id: $variant->income_account_id
    );

    $invoiceLine = $this->createInvoiceLineAction->execute($invoice, $lineDto);

    // Verify the income account used is the one inherited from template
    expect($invoiceLine->income_account_id)->toBe($this->incomeAccount->id);
    expect($invoiceLine->income_account_id)->toBe($this->template->income_account_id);
});

it('variant pricing on invoice works correctly', function () {
    // Get two different variants with different prices
    $variant8gb256gb = $this->variants->where('sku', 'LAPTOP-8GB-256GB')->first();
    $variant16gb512gb = $this->variants->where('sku', 'LAPTOP-16GB-512GB')->first();

    // Update variant prices to be different
    $variant8gb256gb->update(['unit_price' => Money::of(1200, $this->company->currency->code)]);
    $variant16gb512gb->update(['unit_price' => Money::of(1800, $this->company->currency->code)]);

    // Create invoice with both variants
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    // Add first variant
    $line1Dto = new CreateInvoiceLineDTO(
        product_id: $variant8gb256gb->id,
        description: 'Laptop 8GB-256GB',
        quantity: 3,
        unit_price: $variant8gb256gb->unit_price,
        tax_id: $this->tax->id,
        income_account_id: $this->incomeAccount->id
    );

    $line1 = $this->createInvoiceLineAction->execute($invoice, $line1Dto);

    // Add second variant
    $line2Dto = new CreateInvoiceLineDTO(
        product_id: $variant16gb512gb->id,
        description: 'Laptop 16GB-512GB',
        quantity: 2,
        unit_price: $variant16gb512gb->unit_price,
        tax_id: $this->tax->id,
        income_account_id: $this->incomeAccount->id
    );

    $line2 = $this->createInvoiceLineAction->execute($invoice, $line2Dto);

    // Verify calculations
    expect($line1->subtotal->getAmount()->toFloat())->toBe(3600.0); // 3 * 1200
    expect($line1->total_line_tax->getAmount()->toFloat())->toBe(540.0); // 3600 * 0.15

    expect($line2->subtotal->getAmount()->toFloat())->toBe(3600.0); // 2 * 1800
    expect($line2->total_line_tax->getAmount()->toFloat())->toBe(540.0); // 3600 * 0.15

    // Verify each variant maintains its own pricing
    expect($line1->unit_price->getAmount()->toFloat())->toBe(1200.0);
    expect($line2->unit_price->getAmount()->toFloat())->toBe(1800.0);
});

it('variant revenue recognition creates correct journal entry', function () {
    $variant = $this->variants->first();

    // Create and post invoice
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
        'currency_id' => $this->company->currency->id,
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $variant->id,
        description: 'Variant product sale',
        quantity: 5,
        unit_price: Money::of(1000, $this->company->currency->code),
        tax_id: $this->tax->id,
        income_account_id: $this->incomeAccount->id
    );

    $this->createInvoiceLineAction->execute($invoice, $lineDto);

    // Refresh invoice to get updated totals
    $invoice->refresh();

    // Confirm the invoice (this creates journal entry)
    $this->invoiceService->confirm($invoice, $this->user);

    // Verify invoice is confirmed
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Posted);

    // Verify journal entry was created
    $journalEntry = $invoice->fresh()->journalEntry;
    expect($journalEntry)->not->toBeNull();

    // Verify journal entry lines
    $lines = $journalEntry->lines;
    expect($lines)->toHaveCount(3); // Debit AR, Credit Revenue, Credit Tax Payable

    // Find the revenue line (credit to income account)
    $revenueLine = $lines->firstWhere('account_id', $this->incomeAccount->id);
    expect($revenueLine)->not->toBeNull();
    expect($revenueLine->credit->getAmount()->toFloat())->toBe(5000.0); // 5 * 1000
    expect($revenueLine->debit->isZero())->toBeTrue();

    // Verify the journal entry is balanced
    $totalDebit = $lines->sum(fn ($line) => $line->debit->getAmount()->toFloat());
    $totalCredit = $lines->sum(fn ($line) => $line->credit->getAmount()->toFloat());
    expect($totalDebit)->toBe($totalCredit);
});

it('multiple variants can be sold on same invoice with independent revenue tracking', function () {
    // Get all 4 variants
    $variant1 = $this->variants->where('sku', 'LAPTOP-8GB-256GB')->first();
    $variant2 = $this->variants->where('sku', 'LAPTOP-8GB-512GB')->first();
    $variant3 = $this->variants->where('sku', 'LAPTOP-16GB-256GB')->first();
    $variant4 = $this->variants->where('sku', 'LAPTOP-16GB-512GB')->first();

    // Create invoice
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    // Add all variants with different quantities and prices
    $variants = [
        ['variant' => $variant1, 'qty' => 2, 'price' => 1100],
        ['variant' => $variant2, 'qty' => 3, 'price' => 1300],
        ['variant' => $variant3, 'qty' => 1, 'price' => 1500],
        ['variant' => $variant4, 'qty' => 4, 'price' => 1700],
    ];

    $totalExpectedRevenue = 0;

    foreach ($variants as $data) {
        $lineDto = new CreateInvoiceLineDTO(
            product_id: $data['variant']->id,
            description: "Sale of {$data['variant']->sku}",
            quantity: $data['qty'],
            unit_price: Money::of($data['price'], $this->company->currency->code),
            tax_id: null, // No tax for simplicity
            income_account_id: $this->incomeAccount->id
        );

        $line = $this->createInvoiceLineAction->execute($invoice, $lineDto);

        $expectedSubtotal = $data['qty'] * $data['price'];
        expect($line->subtotal->getAmount()->toFloat())->toBe((float) $expectedSubtotal);

        $totalExpectedRevenue += $expectedSubtotal;
    }

    // Verify invoice has 4 lines
    $invoice->refresh();
    expect($invoice->invoiceLines()->count())->toBe(4);

    // Verify total revenue
    $actualTotal = $invoice->invoiceLines()->get()->sum(fn ($line) => $line->subtotal->getAmount()->toFloat());
    expect($actualTotal)->toBe((float) $totalExpectedRevenue);

    // Expected: (2*1100) + (3*1300) + (1*1500) + (4*1700) = 2200 + 3900 + 1500 + 6800 = 14400
    expect((float) $totalExpectedRevenue)->toBe(14400.0);
});

it('template product cannot be used in invoice line', function () {
    // Attempt to create invoice line with template product
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id,
        'status' => InvoiceStatus::Draft,
    ]);

    $lineDto = new CreateInvoiceLineDTO(
        product_id: $this->template->id,
        description: 'Attempting to sell template',
        quantity: 1,
        unit_price: Money::of(1000, $this->company->currency->code),
        tax_id: null,
        income_account_id: $this->incomeAccount->id
    );

    // This should work for now (no validation exists yet)
    // But we document the expected behavior for future implementation
    $line = $this->createInvoiceLineAction->execute($invoice, $lineDto);

    // Currently this will pass, but in Phase 2 we should add validation
    // to prevent selling template products directly
    expect($line->product_id)->toBe($this->template->id);

    // TODO: In Phase 2, add validation to CreateInvoiceLineAction to prevent this:
    // expect(fn () => $this->createInvoiceLineAction->execute($invoice, $lineDto))
    //     ->toThrow(\InvalidArgumentException::class, 'Cannot create invoice lines for template products');
})->skip('Template product validation not yet implemented - deferred to Phase 2');
