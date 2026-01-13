<?php

return [
    'navigation_label' => 'Petty Cash',
    'fund' => 'Petty Cash Fund',
    'funds' => 'Petty Cash Funds',
    'voucher' => 'Petty Cash Voucher',
    'vouchers' => 'Petty Cash Vouchers',
    'replenishment' => 'Petty Cash Replenishment',
    'replenishments' => 'Petty Cash Replenishments',

    // Fund fields
    'fund_name' => 'Fund Name',
    'custodian' => 'Custodian',
    'imprest_amount' => 'Imprest Amount',
    'current_balance' => 'Current Balance',

    // Voucher fields
    'voucher_number' => 'Voucher Number',
    'voucher_date' => 'Voucher Date',
    'expense_category' => 'Expense Category',
    'receipt_reference' => 'Receipt Reference',

    // Replenishment fields
    'replenishment_number' => 'Replenishment Number',
    'replenishment_date' => 'Replenishment Date',
    'payment_method' => 'Payment Method',

    // Statuses
    'status' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'draft' => 'Draft',
        'posted' => 'Posted',
    ],

    // Payment methods
    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'cheque' => 'Cheque',
    ],

    // Actions
    'post_voucher' => 'Post Voucher',
    'close_fund' => 'Close Fund',
    'replenish_fund' => 'Replenish Fund',

    // Messages
    'voucher_posted' => 'Voucher posted successfully',
    'fund_created' => 'Fund created successfully',
    'low_balance_warning' => 'Fund balance is low',
];
