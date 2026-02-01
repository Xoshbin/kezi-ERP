<?php

return [
    'label' => 'Inventory Cost Layer',
    'plural_label' => 'Inventory Cost Layers',
    'navigation_group' => 'Inventory',

    'sections' => [
        'basic_info' => 'Basic Information',
        'quantities_costs' => 'Quantities & Costs',
        'source' => 'Source Information',
    ],

    'fields' => [
        'id' => 'ID',
        'product' => 'Product',
        'purchase_date' => 'Purchase Date',
        'quantity' => 'Original Quantity',
        'remaining_quantity' => 'Remaining Quantity',
        'cost_per_unit' => 'Cost Per Unit',
        'total_cost' => 'Total Cost',
        'remaining_cost' => 'Remaining Cost',
        'source_type' => 'Source Type',
        'source_type_help' => 'The type of document that created this cost layer',
        'source_id' => 'Source ID',
        'source_id_help' => 'The ID of the document that created this cost layer',
        'created_at' => 'Created At',
    ],

    'filters' => [
        'product' => 'Product',
        'source_type' => 'Source Type',
        'purchase_date_from' => 'Purchase Date From',
        'purchase_date_until' => 'Purchase Date Until',
        'depleted' => 'Depleted',
        'active' => 'Active',
    ],

    'source_types' => [
        'stock_move' => 'Stock Move',
        'vendor_bill' => 'Vendor Bill',
        'inventory_adjustment' => 'Inventory Adjustment',
    ],

    'purchase_date' => 'Purchase Date',
    'quantity' => 'Original Quantity',
    'remaining_quantity' => 'Remaining Quantity',
    'cost_per_unit' => 'Cost Per Unit',
    'total_cost' => 'Total Cost',
    'created_at' => 'Created At',

    // Legacy fields for compatibility
    'info' => 'Cost Layer Information',
    'info_description' => 'Cost layers are automatically created for FIFO and LIFO products. They track the cost of inventory purchases and consumption.',
    'remaining_value' => 'Remaining Value',
    'source' => 'Source Document',
    'has_remaining_quantity' => 'Has Remaining Stock',
    'fully_consumed' => 'Fully Consumed',
    'no_cost_layers' => 'No Cost Layers',
    'no_cost_layers_description' => 'Cost layers will appear here when this product uses FIFO or LIFO valuation method and has inventory movements.',
];
