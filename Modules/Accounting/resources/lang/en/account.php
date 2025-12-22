<?php

return [
    // Labels
    'label' => 'Account',
    'plural_label' => 'Accounts',

    // Basic Information
    'company' => 'Company',
    'code' => 'Code',
    'name' => 'Name',
    'type' => 'Type',
    'is_deprecated' => 'Is Deprecated',
    'group' => 'Group',
    'group_help' => 'Accounts are auto-assigned based on code, but you can override manually.',
    'allow_reconciliation' => 'Allow Reconciliation',
    'allow_reconciliation_help' => 'Allow this account to be used in reconciliation processes (A/R, A/P, Bank).',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    // Journal Entry Lines Relation Manager
    'journal_entry_lines' => [
        'label' => 'Journal Entry Line',
        'plural_label' => 'Journal Entry Lines',
        'journal_entry' => 'Journal Entry',
        'debit' => 'Debit',
        'credit' => 'Credit',
        'description' => 'Description',
    ],

    // Section
    'basic_information' => 'Basic Information',
    'basic_information_description' => 'Account code, name, type, and options.',
];
