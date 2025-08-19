<?php

use App\Services\Payments\PaymentStrategyFactory;
use App\Services\Payments\Strategies\SettlementStrategy;
use App\Services\Payments\Strategies\DirectPaymentStrategy;
use App\Enums\Payments\PaymentPurpose;

test('it returns settlement strategy for settlement purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::Settlement);

    // Assert
    expect($strategy)->toBeInstanceOf(SettlementStrategy::class);
});

test('it returns direct payment strategy for loan purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::Loan);

    // Assert
    expect($strategy)->toBeInstanceOf(DirectPaymentStrategy::class);
});

test('it returns direct payment strategy for capital injection purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::CapitalInjection);

    // Assert
    expect($strategy)->toBeInstanceOf(DirectPaymentStrategy::class);
});

test('it returns direct payment strategy for expense claim purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::ExpenseClaim);

    // Assert
    expect($strategy)->toBeInstanceOf(DirectPaymentStrategy::class);
});

test('it returns direct payment strategy for tax payment purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::TaxPayment);

    // Assert
    expect($strategy)->toBeInstanceOf(DirectPaymentStrategy::class);
});

test('it returns direct payment strategy for asset purchase purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make(PaymentPurpose::AssetPurchase);

    // Assert
    expect($strategy)->toBeInstanceOf(DirectPaymentStrategy::class);
});

test('it accepts string values for payment purpose', function () {
    // Act
    $strategy = PaymentStrategyFactory::make('settlement');

    // Assert
    expect($strategy)->toBeInstanceOf(SettlementStrategy::class);
});

test('it throws exception for invalid payment purpose', function () {
    // Act & Assert
    expect(fn() => PaymentStrategyFactory::make('invalid_purpose'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid payment purpose provided: invalid_purpose');
});
