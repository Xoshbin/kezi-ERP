<?php

return [
    // BudgetResource
    'form' => [
        'company_id' => 'Company',
        'name' => 'Name',
        'period_start_date' => 'Period Start Date',
        'period_end_date' => 'Period End Date',
        'budget_type' => 'Budget Type',
        'status' => 'Status',
        'default_status' => 'Draft',
    ],
    'table' => [
        'company_name' => 'Company Name',
        'name' => 'Name',
        'period_start_date' => 'Period Start Date',
        'period_end_date' => 'Period End Date',
        'budget_type' => 'Budget Type',
        'status' => 'Status',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    // BudgetLinesRelationManager
    'budget_lines' => [
        'label' => 'Budget Line',
        'plural_label' => 'Budget Lines',
        'form' => [
            'analytic_account_id' => 'Analytic Account',
            'account_id' => 'Account',
            'budgeted_amount' => 'Budgeted Amount',
            'achieved_amount' => 'Achieved Amount',
            'committed_amount' => 'Committed Amount',
        ],
        'table' => [
            'analytic_account_name' => 'Analytic Account',
            'account_name' => 'Account',
            'budgeted_amount' => 'Budgeted Amount',
            'achieved_amount' => 'Achieved Amount',
            'committed_amount' => 'Committed Amount',
        ],
    ],
];