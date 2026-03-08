<?php

return [
    'common' => [
        'user_not_authenticated' => 'User must be authenticated to perform this action.',
        'journal_entry_not_found' => 'Journal entry not found.',
        'invalid_record_type' => 'Invalid record type.',
        'company_not_found' => 'Company not found.',
        'currency_not_found' => 'Currency not found.',
        'company_base_currency_not_found' => 'Company base currency not found.',
        'currency_id_not_found' => 'Currency with ID :id not found.',
        'default_accounts_payable_missing' => 'Default Accounts Payable account is not configured for this company.',
        'default_tax_account_missing' => 'Default tax account is not configured for this company.',
        'default_purchase_journal_missing' => 'Company default purchase journal is not configured.',
        'product_missing_for_line' => 'Product is missing for line ID :id.',
        'journal_default_debit_account_missing' => 'Journal default debit account not configured.',
        'default_accounts_receivable_missing' => 'Default Accounts Receivable is not configured for this company.',
        'default_accounts_receivable_or_sales_journal_missing' => 'Default Accounts Receivable or Sales Journal is not configured for this company.',
        'tax_account_missing_for_tax' => 'Tax account not configured for tax :tax and no default company input tax account set.',
        'journal_currency_missing' => 'Journal currency is not configured.',
        'default_payroll_journal_missing' => 'Default Payroll Journal is not configured for this company.',
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
        'cannot_modify_posted' => 'Cannot modify a posted journal entry.',
        'failed_to_refresh_after_creation' => 'Failed to refresh journal entry after creation.',
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
        'failed_to_refresh_depreciation_entry' => 'Failed to refresh depreciation entry after update.',
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
        'cannot_generate_opening_entry' => 'Cannot generate Opening Entry: Previous year is open/unbalanced, and no Equity/Retained Earnings account could be found to park the Net Income.',
        'no_miscellaneous_journal_found' => 'No Miscellaneous Journal found for Opening Entry.',
    ],
    'revaluation' => [
        'cannot_be_posted' => 'This revaluation cannot be posted.',
    ],
    'loan' => [
        'company_not_found' => 'Loan company not found.',
        'currency_not_found' => 'Loan currency not found.',
        'currency_missing' => 'Loan currency is missing.',
    ],
    'partner_ledger' => [
        'missing_accounts' => 'Partner ":partner" does not have assigned receivable or payable accounts.',
    ],
    'exchange_gain_loss' => [
        'account_id_required' => 'Realized gain/loss account is required.',
        'bank_journal_required' => 'Company ":company" has no default bank journal configured. Please <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">configure it here</a>.',
    ],
    'inventory_bill' => [
        'only_storable_items' => 'This action should only be called for bills with storable items.',
        'product_missing_inventory_account' => 'Product ID :id is missing default inventory account.',
    ],
    'expense_bill' => [
        'invalid_asset_category' => 'Invalid asset category selected on bill line.',
    ],
    'vendor_bill' => [
        'invalid_asset_category' => 'Invalid asset category on bill line.',
        'product_missing_stock_input_account' => 'Product ID :id missing stock input account.',
    ],
    'cheque' => [
        'invalid_context' => 'Invalid context: :context',
        'handover_only_payable' => 'Handover is only for Payable cheques.',
        'deposit_only_receivable' => 'Deposit is only for Receivable cheques.',
        'default_pdc_payable_missing' => 'Default PDC Payable Account not configured.',
        'default_pdc_receivable_missing' => 'Default PDC Receivable Account not configured.',
    ],
    'payment' => [
        'withholding_tax_account_missing' => 'Withholding Tax account not configured for type: :type',
        'standalone_withholding_needs_partner' => 'Standalone payments cannot have Withholding Tax entries without a partner.',
        'standalone_needs_counterpart_account' => 'Standalone non-partner payments must have a counterpart account.',
    ],
    'withholding_tax' => [
        'at_least_one_entry_required' => 'At least one withholding tax entry must be selected for the certificate.',
        'entries_certified_or_invalid_vendor' => 'Some entries are already certified or do not belong to the specified vendor.',
        'entries_must_have_same_currency' => 'All entries must have the same currency.',
    ],
    'reconciliation' => [
        'default_bank_or_outstanding_receipts_missing' => 'Default bank or outstanding receipts account is not configured for this company.',
    ],
    'payroll' => [
        'payroll_line_has_no_amount' => 'Payroll line :id has no amount.',
    ],
];
