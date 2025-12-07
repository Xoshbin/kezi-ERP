<?php

return [
    'navigation' => [
        'label' => 'Sales Orders',
        'group' => 'Sales',
    ],
    'model' => [
        'label' => 'Sales Order',
        'plural_label' => 'Sales Orders',
    ],
    'fields' => [
        'so_number' => 'Order #',
        'customer' => 'Customer',
        'status' => 'Status',
        'invoicing_status' => 'Invoicing Status',
        'delivery_progress' => 'Delivery Progress',
        'order_date' => 'Order Date',
        'expiration' => 'Expiration',
        'currency' => 'Currency',
        'payment_terms' => 'Payment Terms',
    ],
    'tabs' => [
        'order_lines' => 'Order Lines',
        'other_info' => 'Other Information',
    ],
    'actions' => [
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'create_invoice' => 'Create Invoice',
        'preview' => 'Preview',
    ],
];
