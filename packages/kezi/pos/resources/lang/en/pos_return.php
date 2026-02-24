<?php

return [
    'label' => 'POS Return',
    'plural_label' => 'POS Returns',

    'status' => [
        'label' => 'Status',
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'return_number' => 'Return Number',
    'original_order' => 'Original Order',
    'return_date' => 'Return Date',
    'return_reason' => 'Return Reason',
    'return_notes' => 'Notes',
    'refund_amount' => 'Refund Amount',
    'restocking_fee' => 'Restocking Fee',
    'refund_method' => 'Refund Method',
    'currency' => 'Currency',
    'requested_by' => 'Requested By',
    'approved_by' => 'Approved By',
    'approved_at' => 'Approved At',
    'session' => 'Session',

    'product' => 'Product',
    'quantity_returned' => 'Quantity Returned',
    'unit_price' => 'Unit Price',
    'line_refund_amount' => 'Line Refund',
    'item_condition' => 'Condition',
    'restock' => 'Restock',
    'restock_yes' => 'Yes',
    'restock_no' => 'No',

    'credit_note' => 'Credit Note',
    'credit_note_status' => 'Credit Note Status',
    'payment_reversal' => 'Payment Reversal',
    'payment_reversal_status' => 'Payment Reversal Status',
    'stock_move' => 'Stock Move',

    'section' => [
        'details' => 'Return Details',
        'financials' => 'Financials',
        'people' => 'People',
        'lines' => 'Return Lines',
        'accounting' => 'Accounting Integration',
    ],

    'action' => [
        'approve' => 'Approve Return',
        'reject' => 'Reject Return',
        'reject_reason' => 'Rejection Reason',
        'process' => 'Process Return',
    ],

    'notification' => [
        'approved' => 'Return approved successfully.',
        'rejected' => 'Return rejected.',
        'processed' => 'Return processed successfully.',
        'process_failed' => 'Failed to process return.',
    ],
];
