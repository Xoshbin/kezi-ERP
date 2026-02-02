<?php

return [
    'status' => [
        'pending' => 'Pending',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'pending_description' => 'Payment not yet received',
        'partially_paid_description' => 'Partial payment received',
        'paid_description' => 'Fully paid',
        'cancelled_description' => 'Installment cancelled',
    ],

    'overdue_by_days' => 'Overdue by :days days',
    'paid' => 'Paid',
    'due_today' => 'Due Today',
    'due_in_days' => 'Due in :days days',

    'fields' => [
        'sequence' => 'Installment #',
        'due_date' => 'Due Date',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'remaining_amount' => 'Remaining',
        'status' => 'Status',
        'discount_percentage' => 'Early Payment Discount',
        'discount_deadline' => 'Discount Deadline',
    ],

    'actions' => [
        'apply_payment' => 'Apply Payment',
        'view_payments' => 'View Payments',
        'send_reminder' => 'Send Reminder',
    ],

    'messages' => [
        'payment_applied' => 'Payment applied successfully.',
        'reminder_sent' => 'Payment reminder sent.',
        'early_discount_available' => 'Early payment discount available until :date',
    ],
];
