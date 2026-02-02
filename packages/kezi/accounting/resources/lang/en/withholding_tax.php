<?php

return [
    'label' => 'Withholding Tax',
    // Labels
    'type_label' => 'Withholding Tax Type',
    'types_label' => 'Withholding Tax Types',
    'entry_label' => 'Withholding Tax Entry',
    'entries_label' => 'Withholding Tax Entries',
    'certificate_label' => 'Withholding Tax Certificate',
    'certificates_label' => 'Withholding Tax Certificates',

    // Basic Information
    'basic_information' => 'Basic Information',
    'name' => 'Name',
    'rate' => 'Rate (%)',
    'rate_help' => 'Enter rate as percentage (e.g., 5 for 5%)',
    'withholding_account' => 'Withholding Tax Account',
    'applicable_to' => 'Applicable To',
    'threshold_amount' => 'Threshold Amount',
    'threshold_help' => 'Minimum payment amount before WHT applies (leave blank for no threshold)',
    'is_active' => 'Active',

    // Certificate fields
    'certificate_number' => 'Certificate Number',
    'vendor' => 'Vendor',
    'certificate_date' => 'Certificate Date',
    'period_start' => 'Period Start',
    'period_end' => 'Period End',
    'total_base_amount' => 'Total Base Amount',
    'total_withheld_amount' => 'Total Withheld Amount',
    'status' => 'Status',
    'notes' => 'Notes',

    // Entry fields
    'payment' => 'Payment',
    'base_amount' => 'Base Amount',
    'withheld_amount' => 'Withheld Amount',
    'rate_applied' => 'Rate Applied',
    'certificate' => 'Certificate',

    // Timestamps
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    // Pages
    'pages' => [
        'list' => 'Withholding Tax Types',
        'create' => 'Create Withholding Tax Type',
        'edit' => 'Edit Withholding Tax Type',
        'list_certificates' => 'Withholding Tax Certificates',
        'create_certificate' => 'Create Certificate',
        'view_certificate' => 'View Certificate',
    ],

    // Report
    'report' => [
        'title' => 'Withholding Tax Report',
        'by_vendor' => 'By Vendor',
        'by_type' => 'By Type',
        'uncertified_entries' => 'Uncertified Entries',
        'total_certificates' => 'Total Certificates',
    ],
];
