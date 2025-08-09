<?php

// resources/lang/en/enums.php

return [
    'account_type' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
    ],

    'journal_entry_state' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'reversed' => 'Reversed',
    ],

    'journal_type' => [
        'sale' => 'Sale',
        'purchase' => 'Purchase',
        'bank' => 'Bank',
        'cash' => 'Cash',
        'inventory' => 'Inventory',
        'miscellaneous' => 'Miscellaneous',
    ],

    'lock_date_type' => [
        'tax_return_date' => 'Tax Return Lock',
        'everything_date' => 'All Users Lock',
        'hard_lock' => 'Hard Lock (Immutable)',
    ],

    'asset_status' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'depreciating' => 'Depreciating',
        'fully_depreciated' => 'Fully Depreciated',
        'sold' => 'Sold',
    ],

    'depreciation_entry_status' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
    ],

    'depreciation_method' => [
        'straight_line' => 'Straight Line',
        'declining' => 'Declining Balance',
    ],

    'stock_location_type' => [
        'internal' => 'Internal',
        'customer' => 'Customer',
        'vendor' => 'Vendor',
        'inventory_adjustment' => 'Inventory Adjustment',
    ],

    'stock_move_status' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],

    'stock_move_type' => [
        'incoming' => 'Incoming',
        'outgoing' => 'Outgoing',
        'internal_transfer' => 'Internal Transfer',
        'adjustment' => 'Adjustment',
    ],

    'valuation_method' => [
        'fifo' => 'First In, First Out (FIFO)',
        'lifo' => 'Last In, First Out (LIFO)',
        'avco' => 'Average Cost (AVCO)',
        'standard_price' => 'Standard Price',
    ],

    'partner_type' => [
        'customer' => 'Customer',
        'vendor' => 'Vendor',
    ],

    'product_type' => [
        'product' => 'Product',
        'storable' => 'Storable Product',
        'consumable' => 'Consumable',
        'service' => 'Service',
    ],

    'vendor_bill_status' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
        'paid' => 'Paid',
    ],
];
