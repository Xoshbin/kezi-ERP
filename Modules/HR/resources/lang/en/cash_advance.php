<?php

return [
    'navigation' => [
        'name' => 'Cash Advances',
        'plural' => 'Cash Advances',
        'group' => 'HR Management',
    ],
    'fields' => [
        'employee' => 'Employee',
        'advance_number' => 'Advance Number',
        'amount' => 'Requested Amount',
        'currency' => 'Currency',
        'request_date' => 'Request Date',
        'status' => 'Status',
        'purpose' => 'Purpose',
        'repayment_terms' => 'Repayment Terms',
        'notes' => 'Notes',
        'approved_amount' => 'Approved Amount',
        'approved_at' => 'Approved At',
        'disbursed_at' => 'Disbursed At',
        'settled_at' => 'Settled At',
    ],
    'actions' => [
        'submit' => 'Submit for Approval',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'disburse' => 'Disburse Funds',
        'create_expense_report' => 'Create Expense Report',
        'settle' => 'Settle Advance',
    ],
    'status' => [
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'disbursed' => 'Disbursed',
        'pending_settlement' => 'Pending Settlement',
        'settled' => 'Settled',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],
    'notifications' => [
        'submitted' => 'Cash advance submitted for approval.',
        'approved' => 'Cash advance approved successfully.',
        'rejected' => 'Cash advance rejected.',
        'disbursed' => 'Funds disbursed successfully.',
        'settled' => 'Cash advance settled successfully.',
    ],
];
