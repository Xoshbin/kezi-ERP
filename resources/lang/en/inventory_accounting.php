<?php

return [
    'modes' => [
        'auto_record_on_bill' => 'Auto-Record All Inventory on Bill Confirmation',
        'manual_inventory_recording' => 'Manual Inventory Recording',
    ],

    'descriptions' => [
        'auto_record_on_bill' => 'When confirming a vendor bill, automatically create inventory journal entries for ALL line items. Inventory valuation is recorded immediately upon bill confirmation.',
        'manual_inventory_recording' => 'Inventory journal entries are created separately through the inventory module based on actual goods received quantities. Physical receipt verification happens independently from bill confirmation.',
    ],

    'target_audience' => [
        'auto_record_on_bill' => 'Companies without dedicated inventory staff',
        'manual_inventory_recording' => 'Companies with dedicated inventory/warehouse staff',
    ],

    'use_cases' => [
        'auto_record_on_bill' => 'Smaller companies that want immediate inventory recording upon bill confirmation. Suitable when billed quantities always match received quantities.',
        'manual_inventory_recording' => 'Larger companies with proper receiving processes where physical receipt verification happens independently. Allows for quantity discrepancies between bills and actual receipts.',
    ],

    'field_labels' => [
        'inventory_accounting_mode' => 'Inventory Accounting Mode',
        'inventory_accounting_mode_help' => 'Choose how inventory journal entries are created when vendor bills are confirmed. This setting affects the timing and method of inventory valuation recording.',
    ],

    'section_labels' => [
        'inventory_settings' => 'Inventory Settings',
        'inventory_accounting' => 'Inventory Accounting Configuration',
    ],

    'cost_validation_errors' => [
        'title' => 'Cost Information Required',
        'message' => 'Cannot process inventory movement for ":product_name" because cost information is not available.',
        'explanation' => [
            'avco' => 'This product uses Average Cost valuation, which requires purchase cost information from vendor bills.',
            'fifo' => 'This product uses First In, First Out valuation, which requires purchase cost information from vendor bills.',
            'lifo' => 'This product uses Last In, First Out valuation, which requires purchase cost information from vendor bills.',
        ],
        'solutions' => [
            'no_bills' => 'Create and confirm a vendor bill for this product to establish purchase costs.',
            'draft_bills' => 'Confirm the existing draft vendor bills for this product.',
            'posted_bills_no_cost' => 'Check that the confirmed vendor bills include this product with valid unit prices.',
            'system_issue' => 'Contact your system administrator - cost calculation may need configuration.',
        ],
        'next_steps' => [
            'create_bill' => '1. Go to Vendor Bills and create a new bill',
            'add_product' => '2. Add this product with the correct purchase price',
            'confirm_bill' => '3. Confirm the vendor bill to establish costs',
            'retry_movement' => '4. Return here to process the inventory movement',
        ],
        'help_text' => 'Inventory movements require accurate cost information for proper financial reporting. This ensures your inventory valuation reflects actual purchase costs.',
    ],
];
