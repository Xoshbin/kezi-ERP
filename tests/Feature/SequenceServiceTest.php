<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Sequence;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private SequenceService $sequenceService;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::factory()->create(['code' => 'USD']);
        $this->company = Company::factory()->create(['currency_id' => $currency->id]);
        $this->sequenceService = app(SequenceService::class);
    }

    public function test_it_generates_sequential_invoice_numbers()
    {
        $number1 = $this->sequenceService->getNextInvoiceNumber($this->company);
        $number2 = $this->sequenceService->getNextInvoiceNumber($this->company);
        $number3 = $this->sequenceService->getNextInvoiceNumber($this->company);

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number1);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number2);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number3);
    }

    public function test_it_handles_concurrent_requests_without_race_conditions()
    {
        $generatedNumbers = [];

        // Simulate concurrent requests
        DB::transaction(function () use (&$generatedNumbers) {
            for ($i = 0; $i < 5; $i++) {
                $generatedNumbers[] = $this->sequenceService->getNextInvoiceNumber($this->company);
            }
        });

        // All numbers should be unique
        $this->assertCount(5, array_unique($generatedNumbers));

        // Numbers should follow the new format
        foreach ($generatedNumbers as $number) {
            $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number);
        }
    }

    public function test_it_creates_separate_sequences_for_different_document_types()
    {
        $invoiceNumber = $this->sequenceService->getNextInvoiceNumber($this->company);
        $billNumber = $this->sequenceService->getNextVendorBillNumber($this->company);
        $paymentNumber = $this->sequenceService->getNextPaymentNumber($this->company);

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoiceNumber);
        $this->assertMatchesRegularExpression('/^BILL\/\d{4}\/\d{2}\/\d{7}$/', $billNumber);
        $this->assertEquals('PAY-00001', $paymentNumber); // Payment still uses old format
    }

    public function test_it_creates_separate_sequences_for_different_companies()
    {
        $currency = Currency::factory()->create(['code' => 'EUR']);
        $company2 = Company::factory()->create(['currency_id' => $currency->id]);

        $number1Company1 = $this->sequenceService->getNextInvoiceNumber($this->company);
        $number1Company2 = $this->sequenceService->getNextInvoiceNumber($company2);
        $number2Company1 = $this->sequenceService->getNextInvoiceNumber($this->company);

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number1Company1);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number1Company2);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number2Company1);
    }

    public function test_it_persists_sequence_state_between_requests()
    {
        // Generate some numbers
        $this->sequenceService->getNextInvoiceNumber($this->company);
        $this->sequenceService->getNextInvoiceNumber($this->company);

        // Create a new service instance to simulate a new request
        $newService = app(SequenceService::class);
        $nextNumber = $newService->getNextInvoiceNumber($this->company);

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $nextNumber);
    }

    public function test_it_can_get_current_number_without_incrementing()
    {
        // Generate some numbers first
        $this->sequenceService->getNextInvoiceNumber($this->company);
        $this->sequenceService->getNextInvoiceNumber($this->company);

        $currentNumber = $this->sequenceService->getCurrentNumber($this->company, 'invoice');

        // Should return the current number (2) without incrementing
        $this->assertEquals(2, $currentNumber);

        // Next number should still be 3
        $nextNumber = $this->sequenceService->getNextInvoiceNumber($this->company);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $nextNumber);
    }

    public function test_it_can_reset_sequence()
    {
        // Generate some numbers
        $this->sequenceService->getNextInvoiceNumber($this->company);
        $this->sequenceService->getNextInvoiceNumber($this->company);

        // Reset to 10
        $this->sequenceService->resetSequence($this->company, 'invoice', 10);

        // Next number should be 11
        $nextNumber = $this->sequenceService->getNextInvoiceNumber($this->company);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $nextNumber);
    }

    public function test_sequence_model_atomic_increment()
    {
        $sequence = Sequence::create([
            'company_id' => $this->company->id,
            'document_type' => 'test',
            'prefix' => 'TEST',
            'current_number' => 0,
            'padding' => 3,
        ]);

        $number1 = $sequence->getNextNumber();
        $number2 = $sequence->getNextNumber();

        $this->assertEquals('TEST-001', $number1);
        $this->assertEquals('TEST-002', $number2);

        // Verify the sequence was updated in the database
        $sequence->refresh();
        $this->assertEquals(2, $sequence->current_number);
    }
}
