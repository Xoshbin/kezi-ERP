<?php

return [
    // Labels
    'label' => 'Partner',
    'plural_label' => 'Partners',

    // Basic Information
    'company' => 'Company',
    'name' => 'Name',
    'type' => 'Type',
    'contact_person' => 'Contact Person',
    'email' => 'Email',
    'phone' => 'Phone',
    'tax_id' => 'Tax ID',
    'is_active' => 'Is Active',

    // Address
    'address_line_1' => 'Address Line 1',
    'address_line_2' => 'Address Line 2',
    'city' => 'City',
    'state' => 'State',
    'zip_code' => 'Zip Code',
    'country' => 'Country',

    // Timestamps
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'deleted_at' => 'Deleted At',

    // Relation Managers
    'invoices_relation_manager' => [
        'title' => 'Invoices',
        'invoice_number' => 'Invoice Number',
        'invoice_date' => 'Invoice Date',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'total_amount' => 'Total Amount',
    ],
    'vendor_bills_relation_manager' => [
        'title' => 'Vendor Bills',
        'bill_reference' => 'Bill Reference',
        'bill_date' => 'Bill Date',
        'accounting_date' => 'Accounting Date',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'total_amount' => 'Total Amount',
    ],
    'payments_relation_manager' => [
        'title' => 'Payments',
        'payment_date' => 'Payment Date',
        'amount' => 'Amount',
        'payment_type' => 'Payment Type',
        'reference' => 'Reference',
        'status' => 'Status',
    ],
];