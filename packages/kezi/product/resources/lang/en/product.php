<?php

return [
    'navigation' => [
        'name' => 'Product',
    ],
    // Labels
    'label' => 'Product',
    'plural_label' => 'Products',
    'category' => 'Category',
    'categories' => 'Categories',
    'create' => 'Create Product',

    // Basic Information
    'basic_information' => 'Basic Information',
    'basic_information_description' => 'Enter the basic product details including name, SKU, and type.',
    'company' => 'Company',
    'name' => 'Name',
    'sku' => 'SKU',
    'sku_copied' => 'SKU copied to clipboard!',
    'description' => 'Description',
    'type' => 'Type',

    // Pricing Information
    'pricing_information' => 'Pricing Information',
    'pricing_information_description' => 'Set the default unit price for this product.',
    'unit_price' => 'Unit Price',

    // Accounting Configuration
    'accounting_configuration' => 'Accounting Configuration',
    'accounting_configuration_description' => 'Configure the default income and expense accounts for this product.',
    'income_account' => 'Income Account',
    'expense_account' => 'Expense Account',
    'purchase_tax' => 'Purchase Tax',

    // Inventory Management
    'inventory_management' => 'Inventory Management',
    'inventory_management_description' => 'Configure inventory valuation method and accounting for storable products.',
    'inventory_valuation_method' => 'Valuation Method',
    'inventory_valuation_method_help' => 'Choose how inventory costs are calculated (FIFO, LIFO, AVCO, or Standard Price).',
    'average_cost' => 'Average Cost',
    'average_cost_help' => 'Current average cost per unit (automatically calculated).',
    'default_inventory_account' => 'Inventory Account',
    'default_cogs_account' => 'Cost of Goods Sold Account',
    'default_stock_input_account' => 'Stock Input Account',
    'default_price_difference_account' => 'Price Difference Account',
    'lot_tracking_enabled' => 'Enable Lot Tracking',
    'lot_tracking_enabled_help' => 'Enable lot/batch tracking for this product to track serial numbers, batches, or expiration dates.',

    // Stock Information
    'stock_moves' => 'Stock Movements',
    'inventory_cost_layers' => 'Cost Layers',
    'quantity_on_hand' => 'Quantity On Hand',

    // Status
    'status' => 'Status',
    'status_description' => 'Control whether this product is active and available for use.',
    'is_active' => 'Is Active',
    'is_active_help' => 'Inactive products cannot be used in new transactions.',

    // Filters
    'all_products' => 'All Products',
    'active_products' => 'Active Only',
    'inactive_products' => 'Inactive Only',

    // Legacy fields (for backward compatibility)
    'company_id' => 'Company',
    'income_account_id' => 'Income Account',
    'expense_account_id' => 'Expense Account',
    'sku_label' => 'SKU',
    'sku_column' => 'SKU',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'deleted_at' => 'Deleted At',

    // Variants and Attributes
    'is_template' => 'Is Template',
    'is_template_help' => 'Enable this to create product variants from attributes.',
    'is_variant' => 'Is Variant',
    'variant_attributes' => 'Variant Attributes',
    'variant_attributes_description' => 'Define attributes for this template (e.g., Color, Size)',
    'attributes' => 'Attributes',
    'attribute' => 'Attribute',
    'values' => 'Values',
    'price' => 'Price',
    'on_hand' => 'On Hand',
    'actions' => [
        'generate_variants' => 'Generate Variants',
        'generate_variants_success' => 'Product variants generated successfully.',
    ],
    'attribute_types' => [
        'select' => 'Select',
        'color' => 'Color',
        'radio' => 'Radio',
    ],
    'color_code' => 'Color Code',
    'delete_existing_variants' => 'Delete Existing Variants',
    'delete_existing_variants_help' => 'Check this to remove current variants before generating new ones. Only works if variants have no transactions.',
    'variant_generation' => [
        'options' => 'Options',
        'preview' => 'Preview',
        'select_variants' => 'Select variants to generate',
    ],
];
