<?php

return [
    'label' => 'Stock Quant',
    'plural_label' => 'Stock Quants',

    'sections' => [
        'basic_info' => 'Basic Information',
        'quantities' => 'Quantities',
    ],

    'fields' => [
        'id' => 'ID',
        'product' => 'Product',
        'location' => 'Location',
        'lot' => 'Lot',
        'quantity' => 'Quantity',
        'reserved_quantity' => 'Reserved Quantity',
        'available_quantity' => 'Available Quantity',
        'updated_at' => 'Last Updated',
    ],

    'filters' => [
        'product' => 'Product',
        'location' => 'Location',
        'lot' => 'Lot',
        'low_stock' => 'Low Stock (≤ 10)',
        'out_of_stock' => 'Out of Stock',
        'with_reservations' => 'With Reservations',
    ],

    'no_lot' => 'No Lot',

    'empty_state' => [
        'heading' => 'No stock quants found',
        'description' => 'Stock quants will appear here when products have inventory in locations.',
    ],
];
