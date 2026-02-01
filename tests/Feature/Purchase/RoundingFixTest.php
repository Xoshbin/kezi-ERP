<?php

use Jmeryar\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;

it('handles fractional unit prices with rounding', function () {
    $data = [
        'product_id' => 1,
        'description' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 50.123456, // High precision float
        'currency' => 'USD',
    ];

    $dto = CreatePurchaseOrderLineDTO::fromArray($data);

    expect($dto)->toBeInstanceOf(CreatePurchaseOrderLineDTO::class);
    // 50.123456 rounded HALF_UP at 2 decimals (USD) should be 50.12
    expect($dto->unit_price->getAmount()->toFloat())->toBe(50.12);
});
