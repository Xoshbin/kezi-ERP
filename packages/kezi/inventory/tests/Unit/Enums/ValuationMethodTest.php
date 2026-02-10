<?php

namespace Kezi\Inventory\Tests\Unit\Enums;

use Kezi\Inventory\Enums\Inventory\ValuationMethod;

it('can return correct labels', function () {
    expect(ValuationMethod::Fifo->label())->toBe(__('enums.valuation_method.fifo'))
        ->and(ValuationMethod::Lifo->label())->toBe(__('enums.valuation_method.lifo'))
        ->and(ValuationMethod::Avco->label())->toBe(__('enums.valuation_method.avco'))
        ->and(ValuationMethod::Standard->label())->toBe(__('enums.valuation_method.standard_price'));
});

it('has correct backing values', function () {
    expect(ValuationMethod::Fifo->value)->toBe('fifo')
        ->and(ValuationMethod::Lifo->value)->toBe('lifo')
        ->and(ValuationMethod::Avco->value)->toBe('avco')
        ->and(ValuationMethod::Standard->value)->toBe('standard_price');
});
