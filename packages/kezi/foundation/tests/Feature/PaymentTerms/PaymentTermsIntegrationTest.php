<?php

namespace Kezi\Foundation\Tests\Feature\PaymentTerms;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Kezi\Foundation\Models\PaymentTerm;
use Kezi\Foundation\Models\PaymentTermLine;
use Tests\TestCase;

class PaymentTermsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_create_immediate_payment_term(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => 'Immediate Payment', 'ar' => 'دفع فوري'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Immediate,
            'days' => 0,
            'percentage' => 100,
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $amount = Money::of(1000, 'USD');

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(1, $installments);
        $this->assertEquals($documentDate->toDateString(), $installments[0]['due_date']->toDateString());
        $this->assertTrue($installments[0]['amount']->isEqualTo($amount));
        $this->assertEquals(100.0, $installments[0]['percentage']);
    }

    public function test_can_create_net_30_payment_term(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => 'Net 30', 'ar' => 'صافي 30'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 30,
            'percentage' => 100,
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $amount = Money::of(1000, 'USD');

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(1, $installments);
        $this->assertEquals('2025-02-14', $installments[0]['due_date']->toDateString());
        $this->assertTrue($installments[0]['amount']->isEqualTo($amount));
    }

    public function test_can_create_installment_payment_term(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => '50% in 30 days, 50% in 60 days', 'ar' => '50% خلال 30 يوم، 50% خلال 60 يوم'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'sequence' => 1,
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 30,
            'percentage' => 50,
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'sequence' => 2,
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 60,
            'percentage' => 50,
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $amount = Money::of(1000, 'USD');

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(2, $installments);

        // First installment: 50% in 30 days
        $this->assertEquals('2025-02-14', $installments[0]['due_date']->toDateString());
        $this->assertTrue($installments[0]['amount']->isEqualTo(Money::of(500, 'USD')));
        $this->assertEquals(50.0, $installments[0]['percentage']);

        // Second installment: 50% in 60 days
        $this->assertEquals('2025-03-16', $installments[1]['due_date']->toDateString());
        $this->assertTrue($installments[1]['amount']->isEqualTo(Money::of(500, 'USD')));
        $this->assertEquals(50.0, $installments[1]['percentage']);
    }

    public function test_can_create_end_of_month_payment_term(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => 'End of Month + 15', 'ar' => 'نهاية الشهر + 15'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::EndOfMonth,
            'days' => 15,
            'percentage' => 100,
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $amount = Money::of(1000, 'USD');

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(1, $installments);
        // End of January (2025-01-31) + 15 days = 2025-02-15
        $this->assertEquals('2025-02-15', $installments[0]['due_date']->toDateString());
        $this->assertTrue($installments[0]['amount']->isEqualTo($amount));
    }

    public function test_can_create_day_of_month_payment_term(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => '15th of Next Month', 'ar' => '15 من الشهر القادم'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::DayOfMonth,
            'days' => 0,
            'day_of_month' => 15,
            'percentage' => 100,
        ]);

        $documentDate = Carbon::parse('2025-01-10'); // Before 15th
        $amount = Money::of(1000, 'USD');

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(1, $installments);
        // Since document date is before 15th, due date should be 15th of same month
        $this->assertEquals('2025-01-15', $installments[0]['due_date']->toDateString());

        // Test with document date after 15th
        $documentDate = Carbon::parse('2025-01-20'); // After 15th
        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        // Should be 15th of next month
        $this->assertEquals('2025-02-15', $installments[0]['due_date']->toDateString());
    }

    public function test_payment_term_with_early_payment_discount(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create([
            'name' => ['en' => '2% 10, Net 30', 'ar' => '2% خلال 10، صافي 30'],
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 30,
            'percentage' => 100,
            'discount_percentage' => 2.0,
            'discount_days' => 10,
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $paymentDate = Carbon::parse('2025-01-20'); // Within 10 days
        $amount = Money::of(1000, 'USD');

        $line = $paymentTerm->lines->first();

        $this->assertTrue($line->hasEarlyPaymentDiscount($documentDate, $paymentDate));

        $discountAmount = $line->calculateDiscountAmount($amount, $documentDate, $paymentDate);
        $this->assertTrue($discountAmount->isEqualTo(Money::of(20, 'USD'))); // 2% of 1000

        // Test payment after discount period
        $latePaymentDate = Carbon::parse('2025-01-30'); // After 10 days
        $this->assertFalse($line->hasEarlyPaymentDiscount($documentDate, $latePaymentDate));

        $noDiscount = $line->calculateDiscountAmount($amount, $documentDate, $latePaymentDate);
        $this->assertTrue($noDiscount->isEqualTo(Money::of(0, 'USD')));
    }

    public function test_payment_term_handles_rounding_correctly(): void
    {
        $paymentTerm = PaymentTerm::factory()->for($this->company)->create();

        // Create three installments with percentages that don't divide evenly
        PaymentTermLine::factory()->for($paymentTerm)->create([
            'sequence' => 1,
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 30,
            'percentage' => 33.33,
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'sequence' => 2,
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 60,
            'percentage' => 33.33,
        ]);

        PaymentTermLine::factory()->for($paymentTerm)->create([
            'sequence' => 3,
            'type' => \Kezi\Foundation\Enums\PaymentTerms\PaymentTermType::Net,
            'days' => 90,
            'percentage' => 33.34, // Slightly higher to make total 100%
        ]);

        $documentDate = Carbon::parse('2025-01-15');
        $amount = Money::of(100, 'USD'); // Amount that doesn't divide evenly by 3

        $installments = $paymentTerm->calculateInstallments($documentDate, $amount);

        $this->assertCount(3, $installments);

        // Check that total equals original amount (no rounding errors)
        $total = $installments[0]['amount']
            ->plus($installments[1]['amount'])
            ->plus($installments[2]['amount']);

        $this->assertTrue($total->isEqualTo($amount));
    }
}
