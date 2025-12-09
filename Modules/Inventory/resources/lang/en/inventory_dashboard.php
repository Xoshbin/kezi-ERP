<?php

return [
    // Navigation and Page Titles
    'navigation_label' => 'Dashboard',
    'title' => 'Inventory Dashboard',
    'heading' => 'Inventory Dashboard',
    'subheading' => 'Monitor your inventory performance and key metrics',

    // Filters
    'filters' => [
        'date_from' => 'From Date',
        'date_to' => 'To Date',
        'location' => 'Location',
        'products' => 'Products',
    ],

    // Stats Overview
    'stats' => [
        'total_value' => 'Total Inventory Value',
        'total_value_description' => 'Current value of all inventory',

        'turnover_ratio' => 'Turnover Ratio',
        'turnover_description' => 'Annual inventory turnover rate',

        'low_stock' => 'Low Stock Alerts',
        'low_stock_description' => 'Products below minimum levels',

        'expiring_lots' => 'Expiring Lots',
        'expiring_lots_description' => 'Lots expiring within 30 days',
    ],

    // Charts
    'charts' => [
        'inventory_value' => [
            'title' => 'Inventory Value Trend',
            'description' => 'Track inventory value changes over time',
            'dataset_label' => 'Inventory Value',
        ],

        'turnover' => [
            'title' => 'Receipts vs Deliveries',
            'description' => 'Weekly comparison of stock movements',
            'receipts_label' => 'Receipts',
            'deliveries_label' => 'Deliveries',
        ],

        'aging' => [
            'title' => 'Inventory Aging',
            'description' => 'Distribution of inventory by age',
            'quantity_label' => 'Quantity',
        ],
    ],

    // Quick Actions
    'quick_actions' => [
        'new_receipt' => [
            'title' => 'New Receipt',
            'description' => 'Record incoming inventory',
            'button' => 'Create Receipt',
        ],

        'new_delivery' => [
            'title' => 'New Delivery',
            'description' => 'Record outgoing inventory',
            'button' => 'Create Delivery',
        ],

        'reports' => [
            'title' => 'View Reports',
            'description' => 'Access detailed inventory reports',
            'button' => 'View Reports',
        ],
    ],
];
