<?php

return [
    'navigation' => [
        'name' => 'Bill of Material',
        'plural' => 'Bills of Materials',
        'group' => 'Manufacturing',
    ],
    'fields' => [
        'product' => 'Finished Product',
        'code' => 'BOM Code',
        'name' => 'BOM Name',
        'type' => 'BOM Type',
        'quantity' => 'Quantity to Produce',
        'is_active' => 'Active',
        'notes' => 'Notes',
        'components' => 'Components',
        'qty' => 'Qty',
        'created' => 'Created',
    ],
    'sections' => [
        'info' => 'BOM Information',
    ],
    'types' => [
        'normal' => 'Normal',
        'kit' => 'Kit',
        'phantom' => 'Phantom',
    ],
];
