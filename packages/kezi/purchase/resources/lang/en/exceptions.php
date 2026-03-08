<?php

return [
    'purchase_order' => [
        'cannot_send_state' => 'Purchase order cannot be sent in its current state.',
        'cannot_send_empty' => 'Cannot send purchase order without any lines.',
        'cannot_confirm' => 'Purchase order cannot be confirmed in its current state.',
        'cannot_confirm_empty' => 'Cannot confirm purchase order without any lines.',
        'cannot_cancel' => 'Purchase order cannot be cancelled in its current state.',
        'cannot_receive' => 'Cannot receive goods for this purchase order.',
        'not_fully_billed' => 'Purchase order must be fully billed before it can be marked as done.',
        'cannot_be_updated' => 'Purchase order cannot be updated in its current status.',
        'expected_record' => 'Expected PurchaseOrder record.',
    ],
    'rfq' => [
        'cannot_send_state' => 'RFQ cannot be sent in the current state.',
        'cannot_send_empty' => 'Cannot send RFQ without any lines.',
        'must_be_bid_received' => 'RFQ must be in \'Bid Received\' or \'Accepted\' status to convert.',
        'already_converted' => 'RFQ is already converted to a Purchase Order.',
    ],
    'vendor_bill' => [
        'only_draft_deleted' => 'Only draft vendor bills can be deleted.',
        'only_posted_cancelled' => 'Only posted vendor bills can be cancelled.',
        'cannot_cancel_without_journal' => 'Cannot cancel a bill without a journal entry.',
        'only_posted_reset' => 'Only posted vendor bills can be reset to draft.',
        'cannot_create_line_template' => 'Cannot create vendor bill lines for template products.',
        'only_draft_updated' => 'Only draft vendor bills can be updated.',
        'failed_to_refresh_company' => 'Failed to refresh company for vendor bill.',
        'missing_default_vendor_location' => 'Default Vendor or Stock Location is not configured for Company ID: :company_id.',
        'failed_to_refresh_bill' => 'Failed to refresh vendor bill after creation.',
    ],
    'debit_note' => [
        'posted_paid_only' => 'Debit notes can only be created for posted/paid vendor bills.',
        'company_mismatch' => 'Vendor bill does not belong to the requested company.',
    ],
    'three_way_matching' => [
        'missing_lines' => 'Cannot calculate match status without Purchase Order Line OR Vendor Bill Line.',
        'goods_not_received_post_bill' => 'Cannot post vendor bill: goods have not been received. Please validate the Goods Receipt first.',
    ],
];
