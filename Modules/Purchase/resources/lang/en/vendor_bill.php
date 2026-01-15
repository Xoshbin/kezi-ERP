<?php

return [
    'label' => 'Vendor Bill',
    'plural_label' => 'Vendor Bills',
    'vendor_bill' => 'Vendor Bill',
    'vendor_bills' => 'Vendor Bills',

    // Fields
    'company' => 'Company',
    'vendor' => 'Vendor',
    'currency' => 'Currency',
    'reference' => 'Reference',
    'bill_reference' => 'Bill Reference',
    'bill_date' => 'Bill Date',
    'date' => 'Date',
    'accounting_date' => 'Accounting Date',
    'due_date' => 'Due Date',
    'payment_term' => 'Payment Term',
    'status' => 'Status',
    'payment_state' => 'Payment State',
    'lines' => 'Lines',
    'product' => 'Product',
    'description' => 'Description',
    'quantity' => 'Quantity',
    'unit_price' => 'Unit Price',
    'tax' => 'Tax',
    'expense_account' => 'Expense Account',
    'analytic_account' => 'Analytic Account',
    'total' => 'Total',
    'total_amount' => 'Total Amount',
    'total_tax' => 'Total Tax',
    'journal_entry_id' => 'Journal Entry ID',
    'posted_at' => 'Posted At',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'reason' => 'Reason',
    'account' => 'Account',
    'debit' => 'Debit',
    'credit' => 'Credit',
    'attachments' => 'Attachments',
    'attachments_description' => 'Upload files here',
    'attachments_helper' => 'Allowed types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT. Max size 10MB.',

    // Section Titles and Descriptions
    'vendor_currency_info' => 'Vendor & Currency Information',
    'vendor_currency_info_description' => 'Set the vendor and currency for this bill',
    'bill_details' => 'Bill Details',
    'bill_details_description' => 'Bill reference, dates, and status',
    'line_items' => 'Bill Lines',
    'line_items_description' => 'Add products or services to this bill',
    'company_currency_totals' => 'Totals in Company Currency',

    // Exchange Rate Fields
    'current_exchange_rate' => 'Current Exchange Rate',
    'exchange_rate_helper' => 'This is the current system exchange rate for the selected currency',
    'exchange_rate_helper_with_current' => 'Current Rate: :rate. Leave empty to use current rate, or specify a custom rate.',
    'exchange_rate' => 'Exchange Rate',
    'exchange_rate_at_creation' => 'Exchange Rate at Creation',
    'exchange_rate_locked_helper' => 'Exchange rate is locked for non-draft bills',
    'exchange_rate_manual_helper' => 'Specify a manual rate or leave empty to use current system rate',
    'total_amount_company_currency' => 'Total Amount (Company Currency)',
    'total_tax_company_currency' => 'Total Tax (Company Currency)',

    // Actions
    'actions' => [
        'confirm' => 'Confirm',
        'reset_to_draft' => 'Reset to Draft',
        'confirm_bill' => 'Confirm Bill',
        'load_from_purchase_order' => 'Load from Purchase Order',
        'create_from_purchase_order' => 'Create from Purchase Order',
    ],

    // Fields
    'purchase_order' => 'Purchase Order',
    'no_purchase_order' => 'No Purchase Order',

    // Validation Messages
    'validation_no_line_items' => 'Cannot confirm bill without any line items',
    'validation_zero_total_amount' => 'Cannot confirm bill with zero total amount',

    // Notifications
    'notification_confirm_success' => 'Vendor bill confirmed successfully',
    'notification_confirm_error' => 'Error: Could not confirm vendor bill',
    'notification_reset_success' => 'Vendor bill reset to draft',
    'notification_reset_error' => 'Error: Could not reset vendor bill',
    'notification_bill_confirmed_success' => 'Bill confirmed successfully',
    'notification_confirm_bill_error' => 'Error confirming bill',
    'notification_bill_reset_success' => 'Bill reset to draft',
    'notification_reset_bill_error' => 'Error resetting bill',
    'notification_update_not_allowed' => 'Update not allowed',
];
