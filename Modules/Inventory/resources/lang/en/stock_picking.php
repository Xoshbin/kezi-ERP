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

    // View Stock Picking
    'moves' => 'Moves',
    'total_moves' => 'Total Moves',
    'move_details' => 'Move Details',
    'stock_moves' => 'Stock Moves',
    'product' => 'Product',
    'actual_fulfilled_quantity' => 'Actual / Fulfilled Quantity',
    'assigned_lots' => 'Assigned Lots',
    'validate_done' => 'Validate (Done)',
    'operations' => 'Operations',

    'fields' => [
        'type' => 'Type',
        'state' => 'State',
    ],

    'sections' => [
        'actual_quantities' => 'Actual Quantities',
        'confirm_quantities_description' => 'Confirm the actual quantities that were picked for each move.',
    ],

    'placeholders' => [
        'no_lots_assigned' => 'No lots assigned',
    ],

    'modal' => [
        'assign_picking' => 'Assign Picking',
        'assign' => 'Assign',
        'reserve_stock_description' => 'Reserve stock and assign specific lots for this picking.',
        'stock_moves' => 'Stock Moves',
        'review_moves_description' => 'Review and assign lots for each stock move in this picking.',
        'product' => 'Product',
        'from' => 'From',
        'to' => 'To',
        'lot_assignments' => 'Lot Assignments',
        'lot' => 'Lot',
        'quantity' => 'Quantity',
        'add_lot' => 'Add Lot',
    ],

    'notifications' => [
        'error' => 'Error',
        'validated' => 'Picking Validated',
        'assigned' => 'Picking Assigned',
        'assigned_body' => 'The picking has been assigned successfully. Stock has been reserved and lots have been allocated.',
        'failed_to_assign' => 'Failed to assign picking: :error',
        'no_lines_to_validate' => 'No lines to validate.',
    ],
];
