<?php

return [
    'valuation' => [
        'navigation_label' => 'Inventory Valuation',
        'title' => 'Inventory Valuation Report',
        'heading' => 'Inventory Valuation Report',

        'filters' => [
            'title' => 'Report Filters',
            'as_of_date' => 'As of Date',
            'products' => 'Products',
            'include_reconciliation' => 'Include GL Reconciliation',
            'valuation_method' => 'Valuation Method',
        ],

        'summary' => [
            'total_value' => 'Total Inventory Value',
            'total_quantity' => 'Total Quantity',
            'product_count' => 'Products',
            'as_of_date' => 'As of Date',
        ],

        'reconciliation' => [
            'title' => 'GL Reconciliation',
            'gl_balance' => 'GL Account Balance',
            'calculated_value' => 'Calculated Value',
            'difference' => 'Difference',
            'reconciled' => 'Reconciled',
            'not_reconciled' => 'Not Reconciled',
        ],

        'table' => [
            'title' => 'Product Valuation Details',
            'product' => 'Product',
            'valuation_method' => 'Method',
            'quantity' => 'Quantity',
            'unit_cost' => 'Unit Cost',
            'total_value' => 'Total Value',
            'cost_layers' => 'Cost Layers',
        ],

        'actions' => [
            'export' => 'Export',
            'refresh' => 'Refresh',
            'view_cost_layers' => 'View Cost Layers',
        ],

        'cost_layers_modal' => [
            'title' => 'Cost Layers',
            'purchase_date' => 'Purchase Date',
            'quantity' => 'Quantity',
            'cost_per_unit' => 'Cost per Unit',
            'total_value' => 'Total Value',
            'total' => 'Total',
            'weighted_avg' => 'Weighted Avg',
            'no_layers' => 'No Cost Layers',
            'no_layers_description' => 'This product uses AVCO valuation method or has no inventory movements.',
        ],

        'export_started' => 'Export started successfully.',
        'no_data' => 'No inventory data found',
        'no_data_description' => 'No products have inventory for the selected criteria.',
    ],

    'aging' => [
        'navigation_label' => 'Inventory Aging',
        'title' => 'Inventory Aging Report',
        'heading' => 'Inventory Aging Report',

        'filters' => [
            'title' => 'Report Filters',
            'products' => 'Products',
            'locations' => 'Locations',
            'include_expiration' => 'Include Expiration Analysis',
            'expiration_warning_days' => 'Expiration Warning Days',
        ],

        'summary' => [
            'total_value' => 'Total Inventory Value',
            'total_quantity' => 'Total Quantity',
            'average_age' => 'Average Age (Days)',
            'expiring_soon' => 'Expiring Soon',
        ],

        'buckets' => [
            'title' => 'Age Distribution',
            'quantity' => 'Quantity',
            'value' => 'Value',
            'percentage' => 'Percentage',
        ],

        'expiration' => [
            'title' => 'Expiring Lots',
            'lot_code' => 'Lot Code',
            'product' => 'Product',
            'expiration_date' => 'Expiration Date',
            'days_until_expiration' => 'Days Until Expiration',
            'quantity_on_hand' => 'Quantity on Hand',
        ],
    ],

    'turnover' => [
        'navigation_label' => 'Inventory Turnover',
        'title' => 'Inventory Turnover Report',
        'heading' => 'Inventory Turnover Report',

        'filters' => [
            'title' => 'Report Filters',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'products' => 'Products',
        ],

        'summary' => [
            'total_cogs' => 'Total COGS',
            'average_inventory' => 'Average Inventory Value',
            'turnover_ratio' => 'Turnover Ratio',
            'days_sales_inventory' => 'Days Sales in Inventory',
        ],

        'analysis' => [
            'title' => 'Turnover Analysis',
            'excellent' => 'Excellent (>12x)',
            'good' => 'Good (6-12x)',
            'average' => 'Average (3-6x)',
            'poor' => 'Poor (<3x)',
        ],
    ],

    'lot_trace' => [
        'navigation_label' => 'Lot Traceability',
        'title' => 'Lot Traceability Report',
        'heading' => 'Lot Traceability Report',

        'filters' => [
            'title' => 'Search Criteria',
            'product' => 'Product',
            'lot' => 'Lot',
        ],

        'summary' => [
            'lot_code' => 'Lot Code',
            'product' => 'Product',
            'expiration_date' => 'Expiration Date',
            'current_quantity' => 'Current Quantity',
            'total_value' => 'Total Value',
        ],

        'movements' => [
            'title' => 'Movement History',
            'date' => 'Date',
            'type' => 'Type',
            'quantity' => 'Quantity',
            'from_location' => 'From Location',
            'to_location' => 'To Location',
            'reference' => 'Reference',
            'journal_entry' => 'Journal Entry',
            'valuation_amount' => 'Valuation Amount',
        ],
    ],

    'reorder_status' => [
        'navigation_label' => 'Reorder Status',
        'title' => 'Reorder Status Report',
        'heading' => 'Reorder Status Report',

        'filters' => [
            'title' => 'Report Filters',
            'products' => 'Products',
            'locations' => 'Locations',
            'priority' => 'Priority Level',
        ],

        'summary' => [
            'total_on_hand' => 'Total On Hand',
            'total_reserved' => 'Total Reserved',
            'total_available' => 'Total Available',
            'reorder_warnings' => 'Reorder Warnings',
        ],

        'suggestions' => [
            'title' => 'Reorder Suggestions',
            'product' => 'Product',
            'location' => 'Location',
            'current_stock' => 'Current Stock',
            'min_qty' => 'Min Qty',
            'max_qty' => 'Max Qty',
            'suggested_qty' => 'Suggested Qty',
            'priority' => 'Priority',
            'route' => 'Route',
        ],

        'priority' => [
            'urgent' => 'Urgent',
            'high' => 'High',
            'normal' => 'Normal',
        ],
    ],
];
