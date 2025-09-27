<?php

return [
    // Reconciliation Types
    'type' => [
        'manual_ar_ap' => 'Manual A/R & A/P',
        'manual_ar_ap_description' => 'Manual reconciliation of Accounts Receivable and Accounts Payable',
        'bank_statement' => 'Bank Statement',
        'bank_statement_description' => 'Bank statement reconciliation with payments',
        'manual_general' => 'Manual General',
        'manual_general_description' => 'General manual reconciliation of journal entries',
    ],

    // Company Settings
    'company' => [
        'enable_reconciliation' => 'Enable Reconciliation',
        'enable_reconciliation_help' => 'Enable reconciliation functionality for this company. When disabled, all reconciliation features will be hidden.',
    ],

    // Account Settings
    'account' => [
        'allow_reconciliation' => 'Allow Reconciliation',
        'allow_reconciliation_help' => 'Allow this account to be used in reconciliation processes (A/R, A/P, Bank).',
    ],

    // Partner Unreconciled Entries
    'partner' => [
        'unreconciled_entries_relation_manager' => [
            'title' => 'Unreconciled Entries',
            'entry_date' => 'Entry Date',
            'reference' => 'Reference',
            'account_code' => 'Account Code',
            'account_name' => 'Account Name',
            'description' => 'Description',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'reconcile_selected' => 'Reconcile Selected',
            'reconcile' => 'Reconcile',
            'reconcile_modal_heading' => 'Reconcile Journal Entries',
            'reconcile_modal_description' => 'This will create a reconciliation record linking the selected journal entry lines. Ensure the total debits equal total credits.',
            'reconcile_reference' => 'Reference',
            'reconcile_description' => 'Description',
            'empty_state_heading' => 'No Unreconciled Entries',
            'empty_state_description' => 'All journal entry lines for this partner have been reconciled or there are no entries in reconcilable accounts.',
            'use_bulk_action' => 'Please use the bulk action to reconcile entries.',
            'reconciliation_success' => 'Reconciliation Successful',
            'reconciliation_success_body' => 'Successfully reconciled :count journal entry lines. Reconciliation reference: :reference',
            'reconciliation_error' => 'Reconciliation Error',
            'reconciliation_error_generic' => 'An unexpected error occurred during reconciliation. Please try again.',
        ],
    ],

    // Error Messages
    'errors' => [
        'reconciliation_disabled' => 'Reconciliation functionality is disabled for this company.',
        'account_not_reconcilable' => 'One or more accounts do not allow reconciliation.',
        'unbalanced_reconciliation' => 'The selected entries are not balanced. Total debits must equal total credits.',
        'partner_mismatch' => 'All entries must belong to the same partner for A/R and A/P reconciliation.',
        'already_reconciled' => 'One or more entries are already reconciled.',
        'invalid_entries' => 'Invalid journal entry lines provided.',
        'unposted_entries' => 'Cannot reconcile unposted journal entries.',
    ],

    // Success Messages
    'success' => [
        'reconciliation_created' => 'Reconciliation created successfully.',
        'reconciliation_completed' => 'Reconciliation completed successfully.',
    ],

    // General
    'reconciliation' => 'Reconciliation',
    'reconciliations' => 'Reconciliations',
    'reconciled' => 'Reconciled',
    'unreconciled' => 'Unreconciled',
    'reconciled_at' => 'Reconciled At',
    'reconciled_by' => 'Reconciled By',
    'reference' => 'Reference',
    'description' => 'Description',
    'total_debits' => 'Total Debits',
    'total_credits' => 'Total Credits',
    'balance' => 'Balance',
    'line_count' => 'Line Count',
];
