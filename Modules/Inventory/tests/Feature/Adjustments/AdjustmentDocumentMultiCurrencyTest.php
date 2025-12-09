<?php

namespace Modules\Inventory\Tests\Feature\Adjustments;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Inventory\Actions\Adjustments\CreateAdjustmentDocumentAction;
use Modules\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use Modules\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Inventory\Models\AdjustmentDocumentLine;
use Modules\Inventory\Services\AdjustmentDocumentService;
use Modules\Product\Models\Product;
use Tests\TestCase;
use Tests\Traits\WithConfiguredCompany;

class AdjustmentDocumentMultiCurrencyTest extends TestCase
{
    use RefreshDatabase;
    use WithConfiguredCompany;

    protected Company $company;

    protected Currency $iqd;

    protected Currency $usd;

    protected Product $product;

    protected Tax $tax;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupWithConfiguredCompany();

        // Setup currencies (use firstOrCreate to avoid conflicts)
        $this->iqd = Currency::firstOrCreate(
            ['code' => 'IQD'],
            ['name' => ['en' => 'Iraqi Dinar'], 'symbol' => 'IQD', 'decimal_places' => 3, 'is_active' => true]
        );
        $this->usd = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => ['en' => 'US Dollar'], 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true]
        );

        // Set company base currency to IQD
        $this->company->update(['currency_id' => $this->iqd->id]);

        // Create exchange rate: 1 USD = 1500 IQD
        CurrencyRate::create([
            'company_id' => $this->company->id,
            'currency_id' => $this->usd->id,
            'rate' => 1500.0,
            'effective_date' => Carbon::today(),
        ]);

        // Setup test data
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
        $this->tax = Tax::factory()->create(['company_id' => $this->company->id, 'rate' => 0.10]);
        $this->account = Account::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_creates_adjustment_document_in_company_base_currency()
    {
        $action = app(CreateAdjustmentDocumentAction::class);

        $dto = new CreateAdjustmentDocumentDTO(
            company_id: $this->company->id,
            type: AdjustmentDocumentType::CreditNote,
            date: Carbon::today(),
            reference_number: 'ADJ-001',
            reason: 'Test adjustment',
            currency_id: $this->iqd->id, // Company base currency
            original_invoice_id: null,
            original_vendor_bill_id: null,
            lines: [
                new CreateAdjustmentDocumentLineDTO(
                    product_id: $this->product->id,
                    description: 'Test product',
                    quantity: 2,
                    unit_price: Money::of(100, 'IQD'),
                    tax_id: $this->tax->id,
                    account_id: $this->account->id
                ),
            ]
        );

        $adjustmentDocument = $action->execute($dto);

        // Verify document currency amounts (IQD has 3 decimal places, so 100 IQD = 100000 minor units)
        $this->assertEquals('IQD', $adjustmentDocument->currency->code);
        $this->assertEquals(200000, $adjustmentDocument->subtotal->getMinorAmount()->toInt()); // 2 * 100 IQD = 200000 minor units
        $this->assertEquals(20000, $adjustmentDocument->total_tax->getMinorAmount()->toInt()); // 200 * 0.10 = 20000 minor units
        $this->assertEquals(220000, $adjustmentDocument->total_amount->getMinorAmount()->toInt()); // 200 + 20 = 220000 minor units

        // Verify exchange rate and company currency amounts (should be same as document currency)
        $this->assertEquals(1.0, $adjustmentDocument->exchange_rate_at_creation);
        $this->assertEquals(200000, $adjustmentDocument->subtotal_company_currency->getMinorAmount()->toInt());
        $this->assertEquals(20000, $adjustmentDocument->total_tax_company_currency->getMinorAmount()->toInt());
        $this->assertEquals(220000, $adjustmentDocument->total_amount_company_currency->getMinorAmount()->toInt());

        // Verify line amounts
        $line = $adjustmentDocument->lines->first();
        $this->assertEquals(100000, $line->unit_price->getMinorAmount()->toInt()); // 100 IQD = 100000 minor units
        $this->assertEquals(200000, $line->subtotal->getMinorAmount()->toInt()); // 200 IQD = 200000 minor units
        $this->assertEquals(20000, $line->total_line_tax->getMinorAmount()->toInt()); // 20 IQD = 20000 minor units

        // Company currency line amounts should be same as document currency
        $this->assertEquals(100000, $line->unit_price_company_currency->getMinorAmount()->toInt());
        $this->assertEquals(200000, $line->subtotal_company_currency->getMinorAmount()->toInt());
        $this->assertEquals(20000, $line->total_line_tax_company_currency->getMinorAmount()->toInt());
    }

    /** @test */
    public function it_creates_adjustment_document_in_foreign_currency_with_conversion()
    {
        $action = app(CreateAdjustmentDocumentAction::class);

        $dto = new CreateAdjustmentDocumentDTO(
            company_id: $this->company->id,
            type: AdjustmentDocumentType::CreditNote,
            date: Carbon::today(),
            reference_number: 'ADJ-002',
            reason: 'Test foreign currency adjustment',
            currency_id: $this->usd->id, // Foreign currency
            original_invoice_id: null,
            original_vendor_bill_id: null,
            lines: [
                new CreateAdjustmentDocumentLineDTO(
                    product_id: $this->product->id,
                    description: 'Test product',
                    quantity: 1,
                    unit_price: Money::of(10, 'USD'), // $10 USD
                    tax_id: $this->tax->id,
                    account_id: $this->account->id
                ),
            ]
        );

        $adjustmentDocument = $action->execute($dto);

        // Verify document currency amounts (USD)
        $this->assertEquals('USD', $adjustmentDocument->currency->code);
        $this->assertEquals(1000, $adjustmentDocument->subtotal->getMinorAmount()->toInt()); // $10.00
        $this->assertEquals(100, $adjustmentDocument->total_tax->getMinorAmount()->toInt()); // $1.00
        $this->assertEquals(1100, $adjustmentDocument->total_amount->getMinorAmount()->toInt()); // $11.00

        // Verify exchange rate and company currency amounts (converted to IQD)
        $this->assertEquals(1500.0, $adjustmentDocument->exchange_rate_at_creation);
        $this->assertEquals(15000000, $adjustmentDocument->subtotal_company_currency->getMinorAmount()->toInt()); // $10 * 1500 = 15,000 IQD = 15,000,000 minor units
        $this->assertEquals(1500000, $adjustmentDocument->total_tax_company_currency->getMinorAmount()->toInt()); // $1 * 1500 = 1,500 IQD = 1,500,000 minor units
        $this->assertEquals(16500000, $adjustmentDocument->total_amount_company_currency->getMinorAmount()->toInt()); // $11 * 1500 = 16,500 IQD = 16,500,000 minor units

        // Verify line amounts
        $line = $adjustmentDocument->lines->first();
        $this->assertEquals(1000, $line->unit_price->getMinorAmount()->toInt()); // $10.00
        $this->assertEquals(1000, $line->subtotal->getMinorAmount()->toInt()); // $10.00
        $this->assertEquals(100, $line->total_line_tax->getMinorAmount()->toInt()); // $1.00

        // Company currency line amounts (converted to IQD)
        $this->assertEquals(15000000, $line->unit_price_company_currency->getMinorAmount()->toInt()); // $10 * 1500 = 15,000 IQD = 15,000,000 minor units
        $this->assertEquals(15000000, $line->subtotal_company_currency->getMinorAmount()->toInt()); // $10 * 1500 = 15,000 IQD = 15,000,000 minor units
        $this->assertEquals(1500000, $line->total_line_tax_company_currency->getMinorAmount()->toInt()); // $1 * 1500 = 1,500 IQD = 1,500,000 minor units
    }

    /** @test */
    public function it_handles_missing_exchange_rate_gracefully()
    {
        // Remove the exchange rate
        CurrencyRate::where('currency_id', $this->usd->id)->delete();

        $action = app(CreateAdjustmentDocumentAction::class);

        $dto = new CreateAdjustmentDocumentDTO(
            company_id: $this->company->id,
            type: AdjustmentDocumentType::CreditNote,
            date: Carbon::today(),
            reference_number: 'ADJ-003',
            reason: 'Test missing exchange rate',
            currency_id: $this->usd->id,
            original_invoice_id: null,
            original_vendor_bill_id: null,
            lines: [
                new CreateAdjustmentDocumentLineDTO(
                    product_id: $this->product->id,
                    description: 'Test product',
                    quantity: 1,
                    unit_price: Money::of(10, 'USD'),
                    tax_id: $this->tax->id,
                    account_id: $this->account->id
                ),
            ]
        );

        $adjustmentDocument = $action->execute($dto);

        // Should fallback to rate 1.0 and use original amounts
        $this->assertEquals(1.0, $adjustmentDocument->exchange_rate_at_creation);
        $this->assertEquals(
            $adjustmentDocument->subtotal->getMinorAmount()->toInt(),
            $adjustmentDocument->subtotal_company_currency->getMinorAmount()->toInt()
        );
        $this->assertEquals(
            $adjustmentDocument->total_amount->getMinorAmount()->toInt(),
            $adjustmentDocument->total_amount_company_currency->getMinorAmount()->toInt()
        );
    }

    /** @test */
    public function observer_updates_company_currency_totals_when_lines_change()
    {
        // Create adjustment document in USD
        $adjustmentDocument = AdjustmentDocument::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->usd->id,
            'exchange_rate_at_creation' => 1500.0,
            'subtotal' => Money::of(10, 'USD'),
            'total_tax' => Money::of(1, 'USD'),
            'total_amount' => Money::of(11, 'USD'),
        ]);

        // Add a line - this should trigger the observer
        AdjustmentDocumentLine::factory()->create([
            'adjustment_document_id' => $adjustmentDocument->id,
            'company_id' => $this->company->id,
            'unit_price' => Money::of(5, 'USD'),
            'quantity' => 1,
            'tax_id' => $this->tax->id,
        ]);

        // Refresh the adjustment document to get updated totals
        $adjustmentDocument->refresh();

        // Verify that company currency totals were updated by the observer
        $this->assertNotNull($adjustmentDocument->subtotal_company_currency);
        $this->assertNotNull($adjustmentDocument->total_amount_company_currency);
        $this->assertNotNull($adjustmentDocument->total_tax_company_currency);

        // The observer should have converted the amounts using the exchange rate
        $this->assertEquals('IQD', $adjustmentDocument->subtotal_company_currency->getCurrency()->getCurrencyCode());
    }

    /** @test */
    public function it_creates_correct_journal_entry_for_multi_currency_adjustment_document()
    {
        $action = app(CreateAdjustmentDocumentAction::class);
        $service = app(AdjustmentDocumentService::class);

        // Create a USD adjustment document
        $dto = new CreateAdjustmentDocumentDTO(
            company_id: $this->company->id,
            type: AdjustmentDocumentType::CreditNote,
            date: Carbon::today(),
            reference_number: 'ADJ-USD-001',
            reason: 'Multi-currency credit note',
            currency_id: $this->usd->id, // Foreign currency
            original_invoice_id: null,
            original_vendor_bill_id: null,
            lines: [
                new CreateAdjustmentDocumentLineDTO(
                    product_id: $this->product->id,
                    description: 'Test product',
                    quantity: 1,
                    unit_price: Money::of(100, 'USD'), // $100 USD
                    tax_id: $this->tax->id,
                    account_id: $this->account->id
                ),
            ]
        );

        $adjustmentDocument = $action->execute($dto);

        // Post the adjustment document to create journal entry
        $user = User::factory()->create();
        $service->post($adjustmentDocument, $user);

        // Refresh to get the journal entry
        $adjustmentDocument->refresh();
        $this->assertNotNull($adjustmentDocument->journal_entry_id);

        $journalEntry = $adjustmentDocument->journalEntry;

        // Verify journal entry header currency is USD (document currency) for reference
        $this->assertEquals('USD', $journalEntry->currency->code);

        // Verify journal entry amounts are in company base currency (IQD) - this is correct accounting behavior
        // $100 USD * 1500 rate = 150,000 IQD, plus 10% tax = 165,000 IQD
        $expectedAmountInBaseCurrency = 165000000; // 165,000 IQD = 165,000,000 minor units (IQD has 3 decimal places)
        $this->assertEquals($expectedAmountInBaseCurrency, $journalEntry->total_debit->getMinorAmount()->toInt());
        $this->assertEquals($expectedAmountInBaseCurrency, $journalEntry->total_credit->getMinorAmount()->toInt());

        // Verify journal entry lines are balanced (in company base currency - IQD)
        $totalDebit = Money::of(0, 'IQD');
        $totalCredit = Money::of(0, 'IQD');

        foreach ($journalEntry->lines as $line) {
            $totalDebit = $totalDebit->plus($line->debit);
            $totalCredit = $totalCredit->plus($line->credit);
        }

        $this->assertTrue($totalDebit->isEqualTo($totalCredit));

        // Verify that the journal entry total matches the adjustment document total converted to base currency
        $this->assertEquals(
            $adjustmentDocument->total_amount_company_currency->getMinorAmount()->toInt(),
            $totalDebit->getMinorAmount()->toInt()
        );
    }
}
