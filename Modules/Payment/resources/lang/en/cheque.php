<?php

return [
    'navigation_label' => 'Cheques',
    'plural_label' => 'Cheques',
    'singular_label' => 'Cheque',

    'actions' => [
        'hand_over' => 'Hand Over',
        'deposit' => 'Deposit',
        'clear' => 'Clear',
        'bounce' => 'Bounce',
        'cancel' => 'Cancel',
        'print' => 'Print',
    ],

    'enums' => [
        'cheque_status' => [
            'draft' => 'Draft',
            'printed' => 'Printed',
            'handed_over' => 'Handed Over',
            'deposited' => 'Deposited',
            'cleared' => 'Cleared',
            'bounced' => 'Bounced',
            'cancelled' => 'Cancelled',
            'voided' => 'Voided',
        ],
        'cheque_type' => [
            'payable' => 'Payable',
            'receivable' => 'Receivable',
        ],
    ],

    'fields' => [
        'cheque_number' => 'Cheque Number',
        'amount' => 'Amount',
        'issue_date' => 'Issue Date',
        'due_date' => 'Due Date',
        'memo' => 'Memo',
        'payee' => 'Payee',
        'drawer' => 'Drawer',
        'bank_name' => 'Bank Name',
    ],

    'widgets' => [
        'upcoming_cheques' => 'Upcoming Cheques',
    ],
];
