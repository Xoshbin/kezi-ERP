<?php

namespace Tests\Unit\Enums;

use App\Enums\Payments\PaymentMethod;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_payment_method_enum_has_correct_values(): void
    {
        $expectedValues = [
            'manual',
            'check',
            'bank_transfer',
            'credit_card',
            'debit_card',
            'cash',
            'wire_transfer',
            'ach',
            'sepa',
            'online_payment',
        ];

        $actualValues = array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases());

        $this->assertEquals($expectedValues, $actualValues);
    }

    public function test_payment_method_labels_are_translatable(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            $label = $method->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            // Labels should be properly translated (not return the key)
            $this->assertStringNotContainsString('enums.payment_method.', $label);
        }
    }

    public function test_payment_method_icons_are_valid(): void
    {
        foreach (PaymentMethod::cases() as $method) {
            $icon = $method->icon();
            $this->assertIsString($icon);
            $this->assertStringStartsWith('heroicon-', $icon);
        }
    }

    public function test_payment_method_colors_are_valid(): void
    {
        $validColors = [
            'gray', 'blue', 'green', 'purple', 'yellow',
            'indigo', 'cyan', 'emerald', 'orange', 'red',
        ];

        foreach (PaymentMethod::cases() as $method) {
            $color = $method->color();
            $this->assertIsString($color);
            $this->assertContains($color, $validColors);
        }
    }

    public function test_inbound_methods_returns_correct_methods(): void
    {
        $inboundMethods = PaymentMethod::inboundMethods();

        $this->assertIsArray($inboundMethods);
        $this->assertContains(PaymentMethod::Manual, $inboundMethods);
        $this->assertContains(PaymentMethod::Check, $inboundMethods);
        $this->assertContains(PaymentMethod::BankTransfer, $inboundMethods);
        $this->assertContains(PaymentMethod::CreditCard, $inboundMethods);
        $this->assertContains(PaymentMethod::Cash, $inboundMethods);
        $this->assertContains(PaymentMethod::OnlinePayment, $inboundMethods);
    }

    public function test_outbound_methods_returns_correct_methods(): void
    {
        $outboundMethods = PaymentMethod::outboundMethods();

        $this->assertIsArray($outboundMethods);
        $this->assertContains(PaymentMethod::Manual, $outboundMethods);
        $this->assertContains(PaymentMethod::Check, $outboundMethods);
        $this->assertContains(PaymentMethod::BankTransfer, $outboundMethods);
        $this->assertContains(PaymentMethod::WireTransfer, $outboundMethods);

        // Credit cards and online payments typically not used for outbound business payments
        $this->assertNotContains(PaymentMethod::CreditCard, $outboundMethods);
        $this->assertNotContains(PaymentMethod::OnlinePayment, $outboundMethods);
    }

    public function test_requires_reconciliation_logic(): void
    {
        // Manual and cash payments don't require reconciliation
        $this->assertFalse(PaymentMethod::Manual->requiresReconciliation());
        $this->assertFalse(PaymentMethod::Cash->requiresReconciliation());

        // All other methods require reconciliation
        $this->assertTrue(PaymentMethod::Check->requiresReconciliation());
        $this->assertTrue(PaymentMethod::BankTransfer->requiresReconciliation());
        $this->assertTrue(PaymentMethod::CreditCard->requiresReconciliation());
        $this->assertTrue(PaymentMethod::WireTransfer->requiresReconciliation());
    }

    public function test_supports_batch_processing_logic(): void
    {
        // Electronic transfer methods support batch processing
        $this->assertTrue(PaymentMethod::BankTransfer->supportsBatchProcessing());
        $this->assertTrue(PaymentMethod::WireTransfer->supportsBatchProcessing());
        $this->assertTrue(PaymentMethod::ACH->supportsBatchProcessing());
        $this->assertTrue(PaymentMethod::SEPA->supportsBatchProcessing());

        // Manual methods don't support batch processing
        $this->assertFalse(PaymentMethod::Manual->supportsBatchProcessing());
        $this->assertFalse(PaymentMethod::Check->supportsBatchProcessing());
        $this->assertFalse(PaymentMethod::Cash->supportsBatchProcessing());
        $this->assertFalse(PaymentMethod::CreditCard->supportsBatchProcessing());
    }
}
