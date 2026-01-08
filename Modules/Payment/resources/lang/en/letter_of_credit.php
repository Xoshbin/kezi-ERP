<?php

return [
    'letter_of_credit' => 'Letter of Credit',
    'lc_number' => 'LC Number',
    'bank_reference' => 'Bank Reference',
    'vendor' => 'Beneficiary (Vendor)',
    'issuing_bank' => 'Issuing Bank',
    'amount' => 'LC Amount',
    'utilized_amount' => 'Utilized Amount',
    'balance' => 'Balance',
    'issue_date' => 'Issue Date',
    'expiry_date' => 'Expiry Date',
    'shipment_date' => 'Latest Shipment Date',
    'incoterm' => 'Incoterm',
    'terms_and_conditions' => 'Terms and Conditions',
    'notes' => 'Notes',
    'purchase_order' => 'Purchase Order',
    'type' => 'LC Type',
    'status' => 'LC Status',

    'statuses' => [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'negotiated' => 'Negotiated',
        'partially_utilized' => 'Partially Utilized',
        'fully_utilized' => 'Fully Utilized',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],

    'types' => [
        'import' => 'Import',
        'export' => 'Export',
        'standby' => 'Standby',
    ],

    'charges' => [
        'lc_charge' => 'LC Charge',
        'charge_date' => 'Charge Date',
        'description' => 'Description',
        'journal' => 'Accounting Journal',
        'debit_account' => 'Debit Account',
        'credit_account' => 'Credit Account',
    ],

    'utilizations' => [
        'title' => 'LC Utilizations',
        'vendor_bill' => 'Vendor Bill',
        'utilization_date' => 'Utilization Date',
    ],
];
