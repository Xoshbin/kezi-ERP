<?php

return [
    'common' => [
        'user_not_authenticated' => 'User must be authenticated to perform this action.',
        'journal_entry_not_found' => 'Journal entry not found.',
        'invalid_record_type' => 'Invalid record type.',
    ],
    'lock_date' => [
        'period_locked' => 'The period is locked until :date.',
        'cannot_modify_hard_lock' => 'A hard lock date cannot be modified.',
        'cannot_remove_hard_lock' => 'A hard lock date cannot be removed.',
        'company_required' => 'Company is required for lock date validation.',
    ],
    'balance_sheet' => [
        'not_balanced' => 'Assets (:assets) do not equal Liabilities and Equity (:liabilities_equity).',
    ],
    'budget' => [
        'exceeded' => 'The transaction exceeds the available budget for :budget (Account: :account). Available: :available, Requested: :requested',
    ],
    'journal_entry' => [
        'unbalanced' => 'Cannot post an unbalanced entry.',
        'deletion_not_allowed_posted' => 'Cannot delete a posted journal entry. Corrections must be made with a new reversal entry.',
        'only_posted_can_be_reversed' => 'Only posted journal entries can be reversed.',
    ],
    'journal' => [
        'deletion_not_allowed_entries' => 'Cannot delete a journal with associated journal entries.',
    ],
    'bank_reconciliation' => [
        'no_items_selected' => 'No items selected for reconciliation.',
        'missing_config' => 'Company ":company" is missing default bank or outstanding accounts configuration. Please <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">configure it here</a>.',
        'no_bank_lines' => 'No bank lines provided for reconciliation.',
        'totals_mismatch' => 'Bank statement lines total does not match payments total.',
    ],
    'asset' => [
        'deletion_not_allowed_confirmed' => 'Cannot delete a confirmed asset. Only draft assets can be deleted.',
        'deletion_not_allowed_depreciation' => 'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.',
        'deletion_not_allowed_journal' => 'Cannot delete an asset with associated journal entries. Financial records must be preserved.',
        'default_bank_account_missing' => 'Company default bank account is not configured.',
        'default_bank_journal_missing' => 'Default Bank Journal is not configured for the company.',
        'posted_depreciation_cannot_be_updated' => 'Posted depreciation entries cannot be updated.',
        'posted_depreciation_cannot_be_deleted' => 'Posted depreciation entries cannot be deleted.',
    ],
    'account' => [
        'deletion_not_allowed_financial_records' => 'Cannot delete account with associated financial records.',
    ],
    'tax_report' => [
        'generator_not_found' => 'Tax Report Generator class :class not found.',
        'generator_invalid_contract' => 'Class :class must implement TaxReportGeneratorContract.',
    ],
    'consolidation' => [
        'invoice_company_not_found' => 'Invoice company not found.',
        'reciprocal_vendor_not_found' => 'Reciprocal vendor partner not found in company :target_company linked to :source_company. Please create this partner manually first.',
        'average_rate_period_required' => 'Period start and end dates are required for Average Rate translation.',
        'average_rate_not_calculable' => 'No average rate calculable for :source to :target',
        'unsupported_translation_method' => 'Unsupported translation method: :method',
    ],
    'fiscal_year' => [
        'no_previous_year_found' => 'No previous fiscal year found for company ":company".',
    ],
    'revaluation' => [
        'cannot_be_posted' => 'This revaluation cannot be posted.',
    ],
    'loan' => [
        'company_not_found' => 'Loan company not found.',
        'currency_not_found' => 'Loan currency not found.',
    ],
    'partner_ledger' => [
        'missing_accounts' => 'Partner ":partner" does not have assigned receivable or payable accounts.',
    ],
    'exchange_gain_loss' => [
        'account_id_required' => 'Realized gain/loss account is required.',
        'bank_journal_required' => 'Company ":company" has no default bank journal configured. Please <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">configure it here</a>.',
    ],
];
