<?php

return [
    'navigation' => [
        'name' => 'Expense Reports',
        'label' => 'Expense Report',
        'plural' => 'Expense Reports',
        'group' => 'HR Management',
    ],
    'fields' => [
        'report_number' => 'Report Number',
        'employee' => 'Employee',
        'cash_advance' => 'Linked Cash Advance',
        'report_date' => 'Report Date',
        'status' => 'Status',
        'total_amount' => 'Total Amount',
        'notes' => 'Notes',
        'lines' => 'Expense Lines',
        'company' => 'Company',
    ],
    'lines' => [
        'expense_account' => 'Expense Account',
        'description' => 'Description',
        'amount' => 'Amount',
        'date' => 'Date',
        'receipt' => 'Receipt Ref',
        'partner' => 'Vendor',
    ],
    'actions' => [
        'submit' => 'Submit Report',
        'approve' => 'Approve Report',
        'cash_advance' => 'Cash Advance',
    ],
    'status' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],
    'notifications' => [
        'submitted' => 'Expense report submitted successfully.',
        'approved' => 'Expense report approved.',
        'created' => 'Expense report created successfully.',
    ],
];
