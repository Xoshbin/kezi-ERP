<?php

return [
    'preview_posting' => 'Preview Posting',
    'posting_preview' => 'Posting Preview',
    'export_preview_csv' => 'Export Preview (CSV)',
    'export_preview_pdf' => 'Export Preview (PDF)',

    'errors_title' => 'Errors',

    'links' => [
        'fix_in_accounts' => 'Fix in Accounts',
        'fix_input_tax' => 'Fix Input Tax',
        'open_company' => 'Open Company',
        'open_product' => 'Open Product',
        'open_assets' => 'Open Assets',
        'open_accounts' => 'Open Accounts',
        'open_taxes' => 'Open Taxes',
    ],

    'table' => [
        'account' => 'Account',
        'description' => 'Description',
        'debit' => 'Debit',
        'credit' => 'Credit',
        'totals' => 'Totals',
        'debits' => 'Debits',
        'credits' => 'Credits',
        'balanced' => 'Balanced',
        'unbalanced' => 'Unbalanced',
    ],

    'pdf' => [
        'vendor_bill_heading' => 'Vendor Bill Posting Preview',
        'invoice_heading' => 'Invoice Posting Preview',
        'adjustment_heading' => 'Adjustment Posting Preview',
    ],

    'lines' => [
        'inventory' => 'Inventory: ',
        'asset' => 'Asset: ',
        'input_tax' => 'Input tax: ',
        'revenue' => 'Revenue: ',
        'output_tax' => 'Output tax: ',
        'ap' => 'Accounts Payable',
        'ar' => 'Accounts Receivable',
        'sales_discount' => 'Sales Discount/Contra-Revenue',
        'tax_payable' => 'Tax Payable',
    ],

    'errors' => [
        'ap_account_missing' => 'Company default Accounts Payable account is not configured.',
        'purchase_journal_missing' => 'Company default purchase journal is not configured.',
        'inventory_account_missing' => 'Product ID :product_id is missing its inventory account.',
        'asset_category_invalid' => 'Invalid asset category selected on a bill line.',
        'input_tax_missing' => 'Company input tax account is not configured but taxable lines exist.',

        'ar_account_missing' => 'Company default Accounts Receivable account is not configured.',
        'sales_journal_missing' => 'Company default sales journal is not configured.',
        'income_account_missing' => 'Income account is missing on an invoice line.',
        'tax_account_missing' => 'Selected tax does not have a tax account configured.',

        'sales_discount_missing' => 'Company default Sales Discount account is not configured.',
        'tax_payable_missing' => 'Company default Tax Payable account is not configured.',
    ],
];

