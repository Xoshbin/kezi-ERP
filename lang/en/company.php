<?php

return [
    'singular' => 'Company',
    'plural' => 'Companies',
    'name' => 'Name',
    'address' => 'Address',
    'tax_id' => 'Tax ID',
    'currency_id' => 'Currency',
    'fiscal_country' => 'Fiscal Country',
    'enable_reconciliation' => 'Enable Reconciliation',
    'enable_reconciliation_help' => 'Enable reconciliation functionality for this company. When disabled, all reconciliation features will be hidden.',
    'parent_company_id' => 'Parent Company',
    'default_accounts_payable' => 'Default Accounts Payable',
    'default_tax_receivable' => 'Default Tax Receivable',
    'default_purchase_journal' => 'Default Purchase Journal',
    'default_accounts_receivable' => 'Default Accounts Receivable',
    'default_sales_discount_account' => 'Default Sales Discount Account',
    'default_tax_account' => 'Default Tax Account',
    'default_sales_journal' => 'Default Sales Journal',
    'default_depreciation_journal' => 'Default Depreciation Journal',
    'default_bank_account' => 'Default Bank Account',
    'default_outstanding_receipts_account' => 'Default Outstanding Receipts Account',
    'default_pdc_receivable_account' => 'Default PDC Receivable Account',
    'default_pdc_payable_account' => 'Default PDC Payable Account',
    'default_finished_goods_inventory' => 'Default Finished Goods Inventory',
    'default_wip_account' => 'Default WIP Account',
    'default_manufacturing_overhead_account' => 'Default Manufacturing Overhead Account',
    'default_manufacturing_journal' => 'Default Manufacturing Journal',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    'accounts' => [
        'title' => 'Accounts',
        'code' => 'Code',
        'name' => 'Name',
        'type' => 'Type',
        'is_deprecated' => 'Deprecated',
    ],

    'users' => [
        'title' => 'Users',
        'name' => 'Name',
        'email' => 'Email',
        'email_verified_at' => 'Email Verified At',
        'password' => 'Password',
    ],

    // Sections
    'section' => [
        'details' => 'Company Details',
        'defaults' => 'Company Defaults',
    ],

    'industry_type' => 'Industry Type',
    'inventory_accounting_mode' => 'Inventory Accounting Mode',
    'industries' => [
        'generic' => 'Generic Business',
        'retail' => 'Retail / POS',
        'manufacturing' => 'Manufacturing / MRP',
        'services' => 'Professional Services',
    ],

    'wizard' => [
        'identity' => 'Identity',
        'identity_desc' => 'Tell us about your company basics.',
        'foundation' => 'Foundation',
        'foundation_desc' => 'Set your reporting currency and fiscal home.',
        'profile' => 'Business Profile',
        'profile_desc' => 'What kind of business are you running?',
        'customization' => 'Customization',
        'customization_desc' => 'Almost ready! Final options.',
        'seed_sample_data' => 'Seed Sample Data',
        'seed_sample_data_help' => 'Check this to populate your company with sample customers, vendors, and products to explore.',
    ],
];
