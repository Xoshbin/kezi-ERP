<?php

return [
    'navigation_label' => 'Payments',
    'model_label' => 'Payment',
    'model_plural_label' => 'Payments',

    'form' => [
        'payment_information' => 'Payment Information',
        'direct_payment_description' => 'For non-AR/AP bank movements (fees, taxes, loans, equity, payroll) use Bank Statements/Reconciliation or a Miscellaneous Journal. Payments without document links require a partner and will post to A/R or A/P.',
        'payment_type' => 'Payment Type',
        'partner' => 'Partner',
        'amount' => 'Amount',
        'payment_date' => 'Payment Date',
        'reference' => 'Reference',
        'journal_id' => 'Journal',
        'payment_method' => 'Payment Method',
        'currency_id' => 'Currency',
        'send' => 'Send',
        'receive' => 'Receive',
    ],

    'reference' => 'Reference',
    'partner' => 'Partner',
    'type' => 'Type',
    'method' => 'Method',
    'status' => 'Status',
    'date' => 'Date',
    'amount' => 'Amount',
    'currency' => 'Currency',
    'journal' => 'Journal',
    'company' => 'Company',
    'table' => [
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'action' => [
        'confirm' => [
            'notification' => [
                'success' => 'Payment confirmed successfully',
                'error' => 'Error confirming payment',
            ],
        ],
        'cancel' => [
            'label' => 'Cancel Payment',
            'notification' => [
                'success' => 'Payment Cancelled',
                'success_body' => 'The payment and its journal entry have been successfully reversed.',
                'error' => 'Cancellation Failed',
            ],
        ],
    ],
];
