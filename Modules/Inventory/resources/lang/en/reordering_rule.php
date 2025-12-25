<?php

return [
    'label' => 'Reordering Rule',
    'plural_label' => 'Reordering Rules',
    'create' => 'Add Reordering Rule',

    'sections' => [
        'basic_info' => 'Basic Information',
        'quantities' => 'Quantities',
        'timing' => 'Timing',
    ],

    'fields' => [
        'product' => 'Product',
        'location' => 'Location',
        'route' => 'Route',
        'min_qty' => 'Minimum Quantity',
        'min_qty_help' => 'Trigger reorder when stock falls below this level',
        'max_qty' => 'Maximum Quantity',
        'max_qty_help' => 'Target quantity to reorder up to',
        'safety_stock' => 'Safety Stock',
        'safety_stock_help' => 'Emergency stock level for urgent reorders',
        'multiple' => 'Multiple',
        'multiple_help' => 'Order quantity must be a multiple of this value',
        'lead_time_days' => 'Lead Time (Days)',
        'lead_time_days_help' => 'Expected delivery time in days',
        'active' => 'Active',
        'current_stock' => 'Current Stock',
        'status' => 'Status',
        'updated_at' => 'Last Updated',
    ],

    'filters' => [
        'product' => 'Product',
        'location' => 'Location',
        'route' => 'Route',
        'active' => 'Active',
        'needs_reorder' => 'Needs Reorder',
        'urgent' => 'Urgent',
    ],

    'status' => [
        'inactive' => 'Inactive',
        'urgent' => 'Urgent',
        'reorder_needed' => 'Reorder Needed',
        'ok' => 'OK',
    ],

    'days' => 'days',
];
