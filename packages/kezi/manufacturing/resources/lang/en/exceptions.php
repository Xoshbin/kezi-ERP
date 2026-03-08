<?php

return [
    'order' => [
        'confirm_draft_only' => 'Only draft manufacturing orders can be confirmed.',
        'consume_in_progress_only' => 'Only in-progress manufacturing orders can consume components.',
        'user_required_for_consumption' => 'A user is required to perform component consumption.',
        'consumption_accounts_not_configured' => 'Manufacturing accounts (Raw Materials, WIP, Manufacturing Journal) are not configured for company :company.',
        'manufacturing_accounts_not_configured' => 'Manufacturing accounts (Finished Goods, WIP, Manufacturing Journal) are not configured for company :company.',
        'overhead_account_not_configured' => 'Manufacturing Overhead account is not configured for company :company.',
        'no_scrap_location' => 'No Scrap location found for company :company. Please configure one.',
        'produce_in_progress_only' => 'Only in-progress manufacturing orders can produce finished goods.',
        'user_required_for_production' => 'A user is required to perform production validation.',
        'no_lines_to_process' => 'Manufacturing Order :order has no lines to process.',
        'start_confirmed_only' => 'Only confirmed manufacturing orders can be started.',
    ],
    'bom' => [
        'self_reference' => 'A product cannot be a component of itself in a BOM.',
        'circular_dependency' => 'Circular BOM dependency detected.',
        'max_explosion_depth' => 'Max BOM explosion depth reached (circular dependency?).',
    ],
    'actions' => [
        'edit_order' => 'Edit Order',
        'view_order' => 'View Order',
        'view_stock_locations' => 'View Stock Locations',
        'view_accounting_settings' => 'View Accounting Settings',
    ],
    'notifications' => [
        'confirm_failed' => 'Confirmation Failed',
        'start_failed' => 'Production Start Failed',
        'complete_failed' => 'Production Completion Failed',
        'cancel_failed' => 'Cancellation Failed',
        'scrap_failed' => 'Scrap Failed',
        'order_confirmed' => 'The manufacturing order has been confirmed and is ready for production.',
        'production_started' => 'Components have been consumed and production has started.',
        'production_completed' => 'Finished goods have been added to inventory.',
    ],
];
