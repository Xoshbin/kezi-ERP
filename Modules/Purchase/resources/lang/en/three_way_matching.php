<?php

return [
    'status' => [
        'not_applicable' => 'Not Applicable',
        'pending_receipt' => 'Pending Receipt',
        'partially_received' => 'Partially Received',
        'fully_matched' => 'Fully Matched',
        'quantity_mismatch' => 'Quantity Mismatch',
        'price_mismatch' => 'Price Mismatch',
    ],
    'label' => 'Three-Way Match Status',
    'title' => 'Three-Way Matching',
    'description' => 'Verification of matching between Purchase Order, Goods Receipt Note, and Vendor Bill.',
    'po_vs_grn_vs_bill' => 'Purchase Order vs Goods Receipt vs Vendor Bill',
    'validation' => [
        'goods_not_received' => 'Cannot post vendor bill: goods have not been received. Please validate the Goods Receipt first.',
        'quantity_mismatch' => 'Bill quantity differs from received quantity.',
        'price_mismatch' => 'Bill price differs from purchase order price.',
    ],
];
