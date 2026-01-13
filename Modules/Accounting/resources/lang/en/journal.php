<?php

return [
    // Labels
    'label' => 'Journal',
    'plural_label' => 'Journals',

    // Basic fields
    'company' => 'Company',
    'name' => 'Name',
    'type' => 'Type',
    'short_code' => 'Short Code',
    'currency' => 'Currency',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    // Sections
    'details' => 'Journal Details',
    'details_description' => 'Configure the journal name, type, short code and currency',
    'default_accounts' => 'Default Accounts',
    'default_accounts_description' => 'Optional default debit and credit accounts used by this journal',

    // JournalResource.php
    'default_debit_account' => 'Default Debit Account',
    'default_debit_account_helper' => 'For Bank/Cash journals, this is the bank account to use for payments.',
    'default_credit_account' => 'Default Credit Account',
    'default_credit_account_helper' => 'For Bank/Cash journals, this is the bank account to use for payments.',
    'default_debit_account_short' => 'Default Debit Acct.',
    'default_credit_account_short' => 'Default Credit Acct.',

    // JournalEntriesRelationManager.php
    'entry_date' => 'Entry Date',
    'reference' => 'Reference',
    'description' => 'Description',
    'is_posted' => 'Posted',
    'journal_entries' => 'Journal Entries',
    'fields' => [
        'bank_account' => 'Bank Account',
    ],
];
