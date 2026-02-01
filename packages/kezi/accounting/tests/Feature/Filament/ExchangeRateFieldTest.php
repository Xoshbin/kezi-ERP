<?php

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Kezi\Foundation\Models\Partner;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use Kezi\Purchase\Services\VendorBillService;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;
use Kezi\Sales\Services\InvoiceService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment(); // Add inventory setup for storable products
    $this->actingAs($this->user);

    // Create foreign currency (USD)
    $this->foreignCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => ['en' => 'US Dollar'],
        'symbol' => '$',
        'is_active' => true,
    ]);

    // Create exchange rate
    $this->exchangeRate = 1460.0;
    CurrencyRate::create([
        'currency_id' => $this->foreignCurrency->id,
        'company_id' => $this->company->id,
        'rate' => $this->exchangeRate,
        'effective_date' => Carbon::today(),
        'source' => 'manual',
    ]);

    $this->vendor = Partner::factory()->vendor()->create(['company_id' => $this->company->id]);
    $this->customer = Partner::factory()->customer()->create(['company_id' => $this->company->id]);
});

describe('VendorBill Exchange Rate Field', function () {
    test('exchange rate field is visible for foreign currency vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field is visible
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field is hidden for base currency vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->company->currency_id, // Use base currency from company
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field is not visible
        $livewire->assertFormFieldIsHidden('exchange_rate_at_creation');
    });

    test('exchange rate field exists for draft vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
        ]);

        $livewire = Livewire::test(EditVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate field exists for posted vendor bills', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Posted,
            'exchange_rate_at_creation' => $this->exchangeRate,
        ]);

        $livewire = Livewire::test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\ViewVendorBill::class, ['record' => $vendorBill->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    // ... lines 114-175 kept manual ...

    test('exchange rate field exists for posted invoices', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Posted,
            'exchange_rate_at_creation' => $this->exchangeRate,
        ]);

        $livewire = Livewire::test(\Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\ViewInvoice::class, ['record' => $invoice->id]);

        // Check that the exchange rate field exists
        $livewire->assertFormFieldExists('exchange_rate_at_creation');
    });

    test('exchange rate can be set on draft invoice', function () {
        $customRate = 1500.0;

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
        ]);

        // Directly set the exchange rate to test the field works
        $invoice->update(['exchange_rate_at_creation' => $customRate]);
        $invoice->refresh();

        // Verify the exchange rate was saved
        expect((float) $invoice->exchange_rate_at_creation)->toBe($customRate);
    });
});

describe('Service Behavior with Manual Exchange Rates', function () {
    test('vendor bill service respects manually set exchange rate when posting', function () {
        $customRate = 1500.0;

        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
            'exchange_rate_at_creation' => $customRate,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'bill_date' => Carbon::today(), // Use today's date to match exchange rate
            'accounting_date' => Carbon::today(),
        ]);

        // Add a line item to make the bill valid for posting
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'default_inventory_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets'])->id,
            'default_stock_input_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
        ]);
        VendorBillLine::factory()->create([
            'vendor_bill_id' => $vendorBill->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $vendorBillService = app(VendorBillService::class);

        $vendorBillService->post($vendorBill, $this->user);

        $vendorBill->refresh();

        // Verify the custom exchange rate was preserved
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($customRate);
    });

    test('journal entries use custom exchange rate from vendor bill', function () {
        $customRate = 1310.0; // Custom rate different from stored rate
        $storedRate = 1500.0; // Rate in currency_rates table (different from setup)

        // Clear existing currency rates to avoid conflicts
        CurrencyRate::where('company_id', $this->company->id)
            ->where('currency_id', $this->foreignCurrency->id)
            ->delete();

        // Create a stored exchange rate that should NOT be used
        CurrencyRate::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->foreignCurrency->id,
            'rate' => $storedRate,
            'effective_date' => Carbon::today(),
        ]);

        // Create vendor bill using the proper action to ensure consistency
        $expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'expense',
        ]);

        $vendorBillDto = new CreateVendorBillDTO(
            company_id: $this->company->id,
            vendor_id: $this->vendor->id,
            currency_id: $this->foreignCurrency->id,
            bill_reference: 'TEST-001',
            bill_date: Carbon::today()->toDateString(),
            accounting_date: Carbon::today()->toDateString(),
            due_date: Carbon::today()->addDays(30)->toDateString(),
            lines: [
                new CreateVendorBillLineDTO(
                    product_id: null,
                    description: 'Test expense',
                    quantity: 1,
                    unit_price: Money::of(100, 'USD'),
                    expense_account_id: $expenseAccount->id,
                    tax_id: null,
                    analytic_account_id: null,
                ),
            ],
            created_by_user_id: $this->user->id
        );

        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDto);

        // Set the custom exchange rate
        $vendorBill->update(['exchange_rate_at_creation' => $customRate]);

        $vendorBillService = app(VendorBillService::class);
        $vendorBillService->post($vendorBill, $this->user);

        $vendorBill->refresh();

        // Verify the journal entry was created
        expect($vendorBill->journal_entry_id)->not->toBeNull();
        $journalEntry = $vendorBill->journalEntry;

        // Calculate expected amounts using custom rate (1310) not stored rate (1500)
        // $100 USD * 1310 = 131,000 IQD = 131,000,000 fils (IQD has 3 decimal places)
        $expectedAmountWithCustomRate = (int) (100 * $customRate * 1000); // 131,000,000 fils
        $expectedAmountWithStoredRate = (int) (100 * $storedRate * 1000); // 150,000,000 fils

        // Verify journal entry totals use the CUSTOM rate, not the stored rate
        expect($journalEntry->total_debit->getMinorAmount()->toInt())->toBe($expectedAmountWithCustomRate);
        expect($journalEntry->total_credit->getMinorAmount()->toInt())->toBe($expectedAmountWithCustomRate);

        // Verify it's NOT using the stored rate
        expect($journalEntry->total_debit->getMinorAmount()->toInt())->not->toBe($expectedAmountWithStoredRate);

        // Verify individual line amounts also use custom rate
        $lines = $journalEntry->lines;
        foreach ($lines as $line) {
            if ($line->debit->isGreaterThan(Money::of(0, 'IQD'))) {
                expect($line->debit->getMinorAmount()->toInt())->toBe($expectedAmountWithCustomRate);
                expect($line->exchange_rate_at_transaction)->toBe($customRate);
            }
            if ($line->credit->isGreaterThan(Money::of(0, 'IQD'))) {
                expect($line->credit->getMinorAmount()->toInt())->toBe($expectedAmountWithCustomRate);
                expect($line->exchange_rate_at_transaction)->toBe($customRate);
            }
        }
    });

    test('invoice service respects manually set exchange rate when posting', function () {
        $customRate = 1500.0;

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => InvoiceStatus::Draft,
            'exchange_rate_at_creation' => $customRate,
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'invoice_date' => Carbon::today(), // Use today's date to match exchange rate
        ]);

        // Add a line item to make the invoice valid for posting
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'income_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'income'])->id,
        ]);
        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $invoiceService = app(InvoiceService::class);

        $invoiceService->confirm($invoice, $this->user);

        $invoice->refresh();

        // Verify the custom exchange rate was preserved
        expect((float) $invoice->exchange_rate_at_creation)->toBe($customRate);
    });

    test('vendor bill service uses automatic exchange rate when none is set manually', function () {
        $vendorBill = VendorBill::factory()->create([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'currency_id' => $this->foreignCurrency->id,
            'status' => VendorBillStatus::Draft,
            'exchange_rate_at_creation' => null, // No manual rate set
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(10, 'USD'),
            'bill_date' => Carbon::today(), // Use today's date to match exchange rate
            'accounting_date' => Carbon::today(),
        ]);

        // Add a line item to make the bill valid for posting
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'default_inventory_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_assets'])->id,
            'default_stock_input_account_id' => Account::factory()->create(['company_id' => $this->company->id, 'type' => 'current_liabilities'])->id,
        ]);
        VendorBillLine::factory()->create([
            'vendor_bill_id' => $vendorBill->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
        ]);

        $vendorBillService = app(VendorBillService::class);

        $vendorBillService->post($vendorBill, $this->user);

        $vendorBill->refresh();

        // Verify the automatic exchange rate was used
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe($this->exchangeRate);
    });
});
