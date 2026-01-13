<?php

return [
    'label' => 'Stock Picking',
    'plural_label' => 'Stock Pickings',
    'navigation_label' => 'Stock Pickings',

    // GRN specific
    'goods_receipt' => 'Goods Receipt Note',
    'goods_receipt_plural' => 'Goods Receipt Notes',
    'grn_number' => 'GRN Number',
    'validated_at' => 'Validated At',
    'validated_by' => 'Validated By',

    // Types
    'types' => [
        'receipt' => 'Receipt',
        'delivery' => 'Delivery',
        'internal' => 'Internal',
    ],

    // States
    'states' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'assigned' => 'Assigned',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],

    // Form labels
    'reference' => 'Reference',
    'partner' => 'Partner',
    'origin' => 'Origin',
    'purchase_order' => 'Purchase Order',
    'scheduled_date' => 'Scheduled Date',
    'completed_at' => 'Completed At',

    // Actions
    'validate' => 'Validate',
    'validate_receipt' => 'Validate Receipt',
    'cancel' => 'Cancel',
    'create_backorder' => 'Create Backorder',

    // Messages
    'receipt_validated' => 'Goods Receipt Note validated successfully.',
    'cannot_validate' => 'Cannot validate this stock picking.',
    'already_done' => 'This stock picking has already been completed.',
    'already_cancelled' => 'This stock picking has already been cancelled.',

    // Partial receipt
    'partial_receipt' => 'Partial Receipt',
    'backorder_created' => 'Backorder created for remaining quantity.',
    'remaining_quantity' => 'Remaining Quantity',
    'quantity_to_receive' => 'Quantity to Receive',
    'planned_quantity' => 'Planned Quantity',
    'received_quantity' => 'Received Quantity',
];
