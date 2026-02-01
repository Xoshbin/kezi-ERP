<?php

return [
    'label' => 'Dunning Level',
    'plural_label' => 'Dunning Levels',
    'fields' => [
        'name' => 'Name',
        'days_overdue' => 'Days Overdue',
        'send_email' => 'Send Email',
        'print_letter' => 'Print Letter',
        'charge_fee' => 'Charge Late Fee',
        'fee_product' => 'Fee Product',
        'fee_amount' => 'Fixed Fee Amount',
        'fee_percentage' => 'Fee Percentage',
        'email_subject' => 'Email Subject',
        'email_body' => 'Email Body',
    ],
    'sections' => [
        'general_information' => 'General Information',
        'late_fee_configuration' => 'Late Fee Configuration',
        'email_configuration' => 'Email Configuration',
    ],
    'helpers' => [
        'days_overdue' => 'Number of days past due date to trigger this level',
        'email_configuration' => 'Configure the email template manually.',
    ],
];
