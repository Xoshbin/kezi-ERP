<?php

use Carbon\Carbon;
use Jmeryar\Foundation\Enums\Settings\NumberingType;

describe('NumberingType Enum', function () {
    it('can format numbers with simple type', function () {
        $type = NumberingType::SIMPLE;
        $result = $type->formatNumber('INV', 1, 5);

        expect($result)->toBe('INV-00001');
    });

    it('can format numbers with year prefix type', function () {
        $type = NumberingType::YEAR_PREFIX;
        $date = Carbon::create(2025, 1, 15);
        $result = $type->formatNumber('INV', 1, 5, $date);

        expect($result)->toBe('2025-INV-00001');
    });

    it('can format numbers with year suffix type', function () {
        $type = NumberingType::YEAR_SUFFIX;
        $date = Carbon::create(2025, 1, 15);
        $result = $type->formatNumber('INV', 1, 5, $date);

        expect($result)->toBe('INV-00001-2025');
    });

    it('can format numbers with year month type', function () {
        $type = NumberingType::YEAR_MONTH;
        $date = Carbon::create(2025, 1, 15);
        $result = $type->formatNumber('INV', 1, 5, $date);

        expect($result)->toBe('202501-INV-00001');
    });

    it('can format numbers with slash separated type', function () {
        $type = NumberingType::SLASH_SEPARATED;
        $date = Carbon::create(2025, 1, 15);
        $result = $type->formatNumber('INV', 1, 5, $date);

        expect($result)->toBe('INV/2025/00001');
    });

    it('can format numbers with slash year month type', function () {
        $type = NumberingType::SLASH_YEAR_MONTH;
        $date = Carbon::create(2025, 8, 15);
        $result = $type->formatNumber('INV', 1, 7, $date);

        expect($result)->toBe('INV/2025/08/0000001');
    });

    it('can format numbers with dot separated type', function () {
        $type = NumberingType::DOT_SEPARATED;
        $date = Carbon::create(2025, 1, 15);
        $result = $type->formatNumber('INV', 1, 5, $date);

        expect($result)->toBe('INV.2025.00001');
    });

    it('respects padding parameter', function () {
        $type = NumberingType::SIMPLE;

        $result3 = $type->formatNumber('INV', 1, 3);
        expect($result3)->toBe('INV-001');

        $result7 = $type->formatNumber('INV', 1, 7);
        expect($result7)->toBe('INV-0000001');
    });

    it('handles different prefixes', function () {
        $type = NumberingType::SIMPLE;

        $invoice = $type->formatNumber('INV', 1, 5);
        expect($invoice)->toBe('INV-00001');

        $bill = $type->formatNumber('BILL', 1, 5);
        expect($bill)->toBe('BILL-00001');

        $payment = $type->formatNumber('PAY', 1, 5);
        expect($payment)->toBe('PAY-00001');
    });

    it('can get examples for different prefixes', function () {
        $type = NumberingType::SIMPLE;

        $invExample = $type->getExample('INV');
        expect($invExample)->toBe('INV-00001');

        $billExample = $type->getExample('BILL');
        expect($billExample)->toBe('BILL-00001');
    });

    it('provides examples for different document types', function () {
        $invExample = NumberingType::SIMPLE->getExample('INV');
        $billExample = NumberingType::SIMPLE->getExample('BILL');

        expect($invExample)->toBe('INV-00001');
        expect($billExample)->toBe('BILL-00001');
    });

    it('uses current date when no date provided', function () {
        $type = NumberingType::YEAR_PREFIX;
        $currentYear = now()->year;

        $result = $type->formatNumber('INV', 1, 5);

        expect($result)->toBe("{$currentYear}-INV-00001");
    });
});
