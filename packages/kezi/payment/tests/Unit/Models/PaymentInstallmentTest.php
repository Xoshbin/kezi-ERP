<?php

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Kezi\Payment\Enums\PaymentInstallments\InstallmentStatus;
use Kezi\Payment\Models\PaymentInstallment;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Carbon::setTestNow('2026-01-20 00:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('can be instantiated with default values', function () {
    $installment = PaymentInstallment::factory()->create([
        'company_id' => $this->company->id,
        'status' => InstallmentStatus::Pending,
        'paid_amount' => 0,
    ]);

    expect($installment->status)->toBe(InstallmentStatus::Pending)
        ->and($installment->paid_amount->getAmount()->toFloat())->toBe(0.0)
        ->and($installment->company_id)->toBe($this->company->id);
});

describe('Scopes', function () {
    it('filters overdue installments', function () {
        $overdue = PaymentInstallment::factory()->create([
            'due_date' => now()->subDay(),
            'status' => InstallmentStatus::Pending,
            'company_id' => $this->company->id,
        ]);

        $notOverdue = PaymentInstallment::factory()->create([
            'due_date' => now()->addDay(),
            'status' => InstallmentStatus::Pending,
            'company_id' => $this->company->id,
        ]);

        $paidButPastDue = PaymentInstallment::factory()->create([
            'due_date' => now()->subDay(),
            'status' => InstallmentStatus::Paid,
            'company_id' => $this->company->id,
        ]);

        $results = PaymentInstallment::overdue()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($overdue->id);
    });

    it('filters installments due soon', function () {
        $dueSoon = PaymentInstallment::factory()->create([
            'due_date' => now()->addDays(3),
            'status' => InstallmentStatus::Pending,
            'company_id' => $this->company->id,
        ]);

        $dueFar = PaymentInstallment::factory()->create([
            'due_date' => now()->addDays(10),
            'status' => InstallmentStatus::Pending,
            'company_id' => $this->company->id,
        ]);

        $results = PaymentInstallment::dueSoon(5)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($dueSoon->id);
    });

    it('filters unpaid installments', function () {
        $pending = PaymentInstallment::factory()->create(['status' => InstallmentStatus::Pending, 'company_id' => $this->company->id]);
        $partiallyPaid = PaymentInstallment::factory()->create(['status' => InstallmentStatus::PartiallyPaid, 'company_id' => $this->company->id]);
        $paid = PaymentInstallment::factory()->create(['status' => InstallmentStatus::Paid, 'company_id' => $this->company->id]);

        $results = PaymentInstallment::unpaid()->get();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('id'))->toContain($pending->id, $partiallyPaid->id)
            ->and($results->pluck('id'))->not->toContain($paid->id);
    });
});

describe('Methods', function () {
    it('calculates remaining amount', function () {
        $installment = PaymentInstallment::factory()->create([
            'amount' => 1000,
            'paid_amount' => 400,
            'company_id' => $this->company->id,
        ]);

        expect($installment->getRemainingAmount()->getAmount()->toFloat())->toBe(600.0);
    });

    it('checks if fully paid', function () {
        $installment = PaymentInstallment::factory()->create([
            'amount' => 1000,
            'paid_amount' => 1000,
            'company_id' => $this->company->id,
        ]);

        expect($installment->isFullyPaid())->toBeTrue();

        $installment->paid_amount = Money::of(999, $this->company->currency->code);
        expect($installment->isFullyPaid())->toBeFalse();
    });

    it('checks if overdue', function () {
        $overdue = PaymentInstallment::factory()->create([
            'due_date' => now()->subDay(),
            'paid_amount' => 0,
            'amount' => 1000,
            'company_id' => $this->company->id,
        ]);

        expect($overdue->isOverdue())->toBeTrue();

        $overdue->paid_amount = Money::of(1000, $this->company->currency->code);
        expect($overdue->isOverdue())->toBeFalse();

        $notOverdue = PaymentInstallment::factory()->create([
            'due_date' => now()->addDay(),
            'paid_amount' => 0,
            'company_id' => $this->company->id,
        ]);
        expect($notOverdue->isOverdue())->toBeFalse();
    });

    it('calculates discount amount correctly', function () {
        $installment = PaymentInstallment::factory()->create([
            'amount' => 1000,
            'paid_amount' => 0,
            'discount_percentage' => 10,
            'discount_deadline' => now()->addDays(5),
            'company_id' => $this->company->id,
        ]);

        expect($installment->hasEarlyPaymentDiscount())->toBeTrue()
            ->and($installment->calculateDiscountAmount()->getAmount()->toFloat())->toBe(100.0);

        Carbon::setTestNow(now()->addDays(6));
        expect($installment->hasEarlyPaymentDiscount())->toBeFalse()
            ->and($installment->calculateDiscountAmount()->getAmount()->toFloat())->toBe(0.0);
    });

    it('applies payment and updates status', function () {
        $installment = PaymentInstallment::factory()->create([
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => InstallmentStatus::Pending,
            'company_id' => $this->company->id,
        ]);

        $installment->applyPayment(Money::of(500, $this->company->currency->code));

        expect($installment->paid_amount->getAmount()->toFloat())->toBe(500.0)
            ->and($installment->status)->toBe(InstallmentStatus::PartiallyPaid);

        $installment->applyPayment(Money::of(500, $this->company->currency->code));

        expect($installment->paid_amount->getAmount()->toFloat())->toBe(1000.0)
            ->and($installment->status)->toBe(InstallmentStatus::Paid);
    });

    it('prevents overpayment when applying payment', function () {
        $installment = PaymentInstallment::factory()->create([
            'amount' => 1000,
            'paid_amount' => 0,
            'company_id' => $this->company->id,
        ]);

        $installment->applyPayment(Money::of(1500, $this->company->currency->code));

        expect($installment->paid_amount->getAmount()->toFloat())->toBe(1000.0)
            ->and($installment->status)->toBe(InstallmentStatus::Paid);
    });

    it('gets status description', function () {
        $installment = PaymentInstallment::factory()->create([
            'due_date' => now()->startOfDay()->addDays(5),
            'company_id' => $this->company->id,
        ]);

        // Mock translations or check keys if possible, but standard testing of logic is fine
        expect($installment->getStatusDescription())->toContain('5');

        $installment->due_date = now()->startOfDay()->subDays(2);
        expect($installment->getStatusDescription())->toContain('2');

        $installment->status = InstallmentStatus::Paid;
        $installment->paid_amount = $installment->amount;
        expect($installment->getStatusDescription())->not->toBeEmpty();
    });
});

describe('Relationships', function () {
    it('belongs to a company', function () {
        $installment = PaymentInstallment::factory()->create(['company_id' => $this->company->id]);
        expect($installment->company)->toBeInstanceOf(Company::class)
            ->and($installment->company->id)->toBe($this->company->id);
    });

    it('has morph relationship to installmentable', function () {
        $invoice = Invoice::factory()->create(['company_id' => $this->company->id]);
        $installment = PaymentInstallment::factory()->create([
            'installment_type' => Invoice::class,
            'installment_id' => $invoice->id,
            'company_id' => $this->company->id,
        ]);

        expect($installment->installmentable)->toBeInstanceOf(Invoice::class)
            ->and($installment->installmentable->id)->toBe($invoice->id);
    });
});
