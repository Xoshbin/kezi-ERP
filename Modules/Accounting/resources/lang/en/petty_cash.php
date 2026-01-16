<?php

return [
    'navigation_label' => 'Petty Cash',
    'replenishment' => [
        'label' => 'Petty Cash Replenishment',
        'plural_label' => 'Petty Cash Replenishments',
        'section_details' => 'Replenishment Details',
        'replenishment_number' => 'Replenishment Number',
    ],
    'replenishments' => 'Petty Cash Replenishments',

    'fund' => [
        'label' => 'Petty Cash Fund',
        'plural_label' => 'Petty Cash Funds',
        'section_details' => 'Fund Details',
    ],
    'funds' => 'Petty Cash Funds',

    'voucher' => [
        'label' => 'Petty Cash Voucher',
        'plural_label' => 'Petty Cash Vouchers',
        'expense_details' => 'Expense Details',
        'voucher_number' => 'Voucher Number',
        'post_modal_heading' => 'Post Petty Cash Voucher',
        'post_modal_description' => 'This will create a journal entry and update the fund balance.',
    ],
    'vouchers' => 'Petty Cash Vouchers',

    'fields' => [
        'petty_cash_fund' => 'Petty Cash Fund',
        'fund_name' => 'Fund Name',
        'imprest_amount' => 'Imprest Amount',
        'current_balance' => 'Current Balance',
        'replenishment_date' => 'Replenishment Date',
        'replenishment_number' => 'Replenishment Number',
        'amount' => 'Amount',
        'payment_method' => 'Payment Method',
        'reference' => 'Reference',
        'expense_date' => 'Expense Date',
        'voucher_date' => 'Voucher Date',
        'expense_category' => 'Expense Category',
        'vendor_payee' => 'Vendor/Payee (Optional)',
        'description' => 'Description',
        'receipt_reference' => 'Receipt Reference',
        'custodian' => 'Custodian',
        'voucher_number' => 'Voucher Number',
    ],
    'actions' => [
        'post' => 'Post',
        'post_voucher' => 'Post Voucher',
        'close_fund' => 'Close Fund',
        'replenish_fund' => 'Replenish Fund',
    ],
    'status' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'draft' => 'Draft',
        'posted' => 'Posted',
    ],
    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'cheque' => 'Cheque',
    ],
    'messages' => [
        'voucher_posted' => 'Voucher posted successfully',
        'fund_created' => 'Fund created successfully',
        'low_balance_warning' => 'Fund balance is low',
    ],
    'helpers' => [
        'replenishment_amount' => 'Amount will be auto-calculated based on fund balance',
        'replenishment_reference' => 'Bank transfer reference or cheque number',
        'expense_category' => 'Select the type of expense',
        'expense_description' => 'Describe the purpose of this expense',
        'receipt_reference' => 'External receipt number',
    ],
];
