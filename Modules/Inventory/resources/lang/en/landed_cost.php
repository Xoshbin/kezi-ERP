<?php

return [
    'navigation_label' => 'Landed Costs',
    'label' => 'Landed Cost',
    'plural_label' => 'Landed Costs',
    'navigation_group' => 'Operations',
    'section_details' => 'Landed Cost Details',
    'fields' => [
        'description' => 'Description',
        'vendor_bill' => 'Vendor Bill',
        'total_amount' => 'Total Amount',
        'id' => 'ID',
        'reference' => 'Reference',
        'scheduled_date' => 'Scheduled Date',
        'type' => 'Type',
        'state' => 'State',
        'from' => 'From',
        'to' => 'To',
        'attach_stock_picking' => 'Attach Stock Picking',
        'date' => 'Date',
        'allocation_method' => 'Allocation Method',
        'status' => 'Status',
    ],
    'actions' => [
        'post_landed_cost' => 'Post Landed Cost',
    ],
    'notifications' => [
        'no_pickings' => 'No Stock Pickings Attached',
        'no_pickings_body' => 'Please attach at least one stock picking before posting.',
        'posted' => 'Landed Cost Posted',
    ],
];
