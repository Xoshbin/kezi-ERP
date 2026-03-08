<?php

return [
    'pos_return' => [
        'only_draft_can_be_submitted' => 'Only draft returns can be submitted.',
        'only_approved_can_be_processed' => 'Only approved returns can be processed.',
        'cannot_be_rejected_in_status' => 'Return cannot be rejected in current status.',
        'cannot_be_approved_in_status' => 'Return cannot be approved in current status.',
    ],
    'common' => [
        'no_sales_journal_found' => 'No Sales Journal found for company :company.',
        'no_payment_journal_configured' => 'No Payment Journal configured for POS Profile.',
        'no_stock_location_restocking' => 'No stock location configured for restocking.',
        'invalid_session' => 'Invalid or unauthorized session.',
        'currency_not_found' => 'Currency ID :id not found.',
        'no_sales_journal_found_configure' => 'No Sales Journal found for Company :company. Please configure a Sales Journal.',
        'no_associated_session' => 'POS Order :uuid has no associated session.',
        'no_payment_journal_register_payment' => 'No Payment Journal configured for POS Profile. Cannot register payment for Order :order.',
    ],
];
