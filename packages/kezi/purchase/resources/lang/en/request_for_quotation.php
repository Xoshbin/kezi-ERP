<?php

return [
    'label' => 'Request for Quotation',
    'plural_label' => 'Request for Quotations',
    'navigation_label' => 'Request for Quotations',
    'fields' => [
        'rfq_number' => 'RFQ Number',
        'vendor' => 'Vendor',
        'company' => 'Company',
        'rfq_date' => 'RFQ Date',
        'valid_until' => 'Valid Until',
        'currency' => 'Currency',
        'exchange_rate' => 'Exchange Rate',
        'status' => 'Status',
        'subtotal' => 'Subtotal',
        'tax_total' => 'Tax Total',
        'total' => 'Total',
        'date' => 'Date',
        'bid_notes' => 'Bid Notes',
        'vendor_reference' => 'Vendor Reference',
        'notes' => 'Notes',
    ],
    'sections' => [
        'general' => 'General Information',
        'basic_info' => 'Basic Information',
        'vendor_info' => 'Vendor Details',
        'line_items' => 'Line Items',
        'totals' => 'Totals',
        'details' => 'Details',
        'notes' => 'Notes',
    ],
    'lines' => [
        'product' => 'Product',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        'unit_price' => 'Unit Price',
        'tax' => 'Tax',
    ],
    'actions' => [
        'record_bid' => 'Record Bid',
        'send_to_vendor' => 'Send to Vendor',
        'convert_to_order' => 'Convert to Order',
    ],
    'notifications' => [
        'po_created_success' => 'Purchase Order created successfully',
    ],
];
