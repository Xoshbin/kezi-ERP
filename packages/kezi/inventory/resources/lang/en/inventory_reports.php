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
        'export_failed' => 'Export failed',
        'no_data_to_export' => 'No data available to export',
        'export_confirmation' => 'Export Inventory Valuation Report',
        'export_description' => 'This will generate a CSV file containing the current inventory valuation data. The file will be downloaded to your device.',
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
            'age_range' => 'Age Range',
            'quantity' => 'Quantity',
            'value' => 'Value',
            'percentage' => 'Percentage',
            'products' => 'Products',
            'total' => 'Total',
        ],

        'expiration' => [
            'title' => 'Expiring Lots',
            'lot_code' => 'Lot Code',
            'product' => 'Product',
            'expiration_date' => 'Expiration Date',
            'days_until_expiration' => 'Days Until Expiration',
            'quantity_on_hand' => 'Quantity on Hand',
        ],

        'days' => 'days',
        'days_ago' => 'days ago',
        'expired' => 'Expired',
        'export_started' => 'Export started successfully.',
        'export_failed' => 'Export failed',
        'no_data_to_export' => 'No data available to export',
        'actions' => [
            'export' => 'Export',
            'refresh' => 'Refresh',
        ],

        'export_confirmation' => 'Export Inventory Aging Report',
        'export_description' => 'This will generate a CSV file containing the inventory aging analysis. The file will be downloaded to your device.',
        'no_data' => 'No aging data found',
        'no_data_description' => 'No inventory found for the selected criteria.',
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
            'ratio_explanation' => 'Your inventory turns over :ratio times during this period.',
        ],

        'benchmarks' => [
            'excellent' => 'Inventory turns over more than 12 times per year',
            'good' => 'Inventory turns over 6-12 times per year',
            'average' => 'Inventory turns over 3-6 times per year',
            'poor' => 'Inventory turns over less than 3 times per year',
        ],

        'period_info' => [
            'title' => 'Period Information',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'period_length' => 'Period Length',
        ],

        'days' => 'days',
        'annualized' => 'Annualized',
        'actions' => [
            'export' => 'Export',
            'refresh' => 'Refresh',
        ],
        'export_started' => 'Export started successfully.',
        'export_failed' => 'Export failed',
        'no_data_to_export' => 'No data available to export',
        'no_data' => 'No turnover data found',
        'no_data_description' => 'No COGS or inventory movements found for the selected period.',
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
            'title' => 'Lot Summary',
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
            'incoming' => 'Incoming Movements',
            'outgoing' => 'Outgoing Movements',
            'internal' => 'Internal Movements',
            'count' => 'movements',
        ],

        'actions' => [
            'export' => 'Export',
            'refresh' => 'Refresh',
        ],

        'no_expiration' => 'No Expiration',
        'export_started' => 'Export started successfully.',
        'export_failed' => 'Export failed',
        'no_data_to_export' => 'No data available to export',
        'no_selection' => 'Select Product and Lot',
        'no_selection_description' => 'Please select a product and lot to view traceability information.',
        'no_movements' => 'No movements found',
        'no_movements_description' => 'This lot has no recorded movements in the system.',
    ],

    'reorder' => [
        'navigation_label' => 'Reorder Status',
        'title' => 'Reorder Status Report',
        'heading' => 'Reorder Status Report',

        'filters' => [
            'title' => 'Report Filters',
            'products' => 'Products',
            'locations' => 'Locations',
            'include_suggested_orders' => 'Include Suggested Orders',
            'include_overstock' => 'Include Overstock Items',
        ],

        'summary' => [
            'critical' => 'Critical Items',
            'low_stock' => 'Low Stock',
            'suggested' => 'Suggested Orders',
            'overstock' => 'Overstock',
            'suggested_value' => 'Suggested Value',
        ],

        'alerts' => [
            'critical_title' => 'Critical Stock Alert',
            'critical_description' => ':count products are critically low and need immediate attention.',
        ],

        'table' => [
            'title' => 'Reorder Status',
            'product' => 'Product',
            'location' => 'Location',
            'current_quantity' => 'Current Qty',
            'min_quantity' => 'Min Qty',
            'max_quantity' => 'Max Qty',
            'suggested_quantity' => 'Suggested Qty',
            'status' => 'Status',
            'estimated_cost' => 'Estimated Cost',
        ],

        'actions' => [
            'export' => 'Export',
            'refresh' => 'Refresh',
        ],

        'export_started' => 'Export started successfully.',
        'export_failed' => 'Export failed',
        'no_data_to_export' => 'No data available to export',
        'no_data' => 'No reorder data found',
        'no_data_description' => 'No products with reordering rules found for the selected criteria.',
    ],
];
