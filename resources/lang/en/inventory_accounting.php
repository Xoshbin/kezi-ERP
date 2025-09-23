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
];
