<?php

return [
    'label' => 'Purchase Order',
    'plural_label' => 'Purchase Orders',

    'navigation' => [
        'label' => 'Purchase Orders',
        'group' => 'Purchases',
    ],

    'sections' => [
        'basic_info' => 'Basic Information',
        'vendor_details' => 'Vendor Details',
        'delivery_info' => 'Delivery Information',
        'notes' => 'Notes & Terms',
        'totals' => 'Totals',
    ],

    'fields' => [
        'po_number' => 'PO Number',
        'status' => 'Status',
        'po_date' => 'PO Date',
        'reference' => 'Reference',
        'vendor' => 'Vendor',
        'currency' => 'Currency',
        'expected_delivery_date' => 'Expected Delivery Date',
        'delivery_location' => 'Delivery Location',
        'notes' => 'Notes',
        'terms_and_conditions' => 'Terms & Conditions',
        'total_amount' => 'Total Amount',
        'total_tax' => 'Total Tax',
        'created_by' => 'Created By',
        'created_at' => 'Created At',
    ],

    'help' => [
        'po_number' => 'Auto-generated when saved',
        'reference' => 'Your internal reference or vendor quote number',
        'terms_and_conditions' => 'Standard terms and conditions for this purchase order',
    ],

    'actions' => [
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
    ],

    'notifications' => [
        'confirmed' => 'Purchase order confirmed successfully',
        'cancelled' => 'Purchase order cancelled successfully',
    ],

    'status' => [
        // Pre-commitment phase
        'rfq' => 'Request for Quotation',
        'rfq_sent' => 'RFQ Sent',

        // Commitment phase
        'draft' => 'Draft',
        'sent' => 'Sent',
        'confirmed' => 'Confirmed',

        // Fulfillment phase
        'to_receive' => 'To Receive',
        'partially_received' => 'Partially Received',
        'fully_received' => 'Fully Received',

        // Billing phase
        'to_bill' => 'To Bill',
        'partially_billed' => 'Partially Billed',
        'fully_billed' => 'Fully Billed',

        // Final states
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ],

    'sections' => [
        'basic_info' => 'Basic Information',
        'vendor_details' => 'Vendor Details',
        'delivery_info' => 'Delivery Information',
        'totals' => 'Totals',
        'lines' => 'Order Lines',
        'notes' => 'Notes & Terms',
    ],

    'fields' => [
        'id' => 'ID',
        'po_number' => 'PO Number',
        'status' => 'Status',
        'reference' => 'Reference',
        'vendor' => 'Vendor',
        'currency' => 'Currency',
        'po_date' => 'PO Date',
        'expected_delivery_date' => 'Expected Delivery Date',
        'confirmed_at' => 'Confirmed At',
        'cancelled_at' => 'Cancelled At',
        'exchange_rate_at_creation' => 'Exchange Rate',
        'total_amount' => 'Total Amount',
        'total_tax' => 'Total Tax',
        'total_amount_company_currency' => 'Total (Company Currency)',
        'total_tax_company_currency' => 'Tax (Company Currency)',
        'notes' => 'Notes',
        'terms_and_conditions' => 'Terms & Conditions',
        'delivery_location' => 'Delivery Location',
        'created_by_user' => 'Created By',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'billing_status' => 'Billing Status',
    ],

    'line_fields' => [
        'product' => 'Product',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'quantity_received' => 'Quantity Received',
        'remaining_quantity' => 'Remaining',
        'unit_price' => 'Unit Price',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'total_line_tax' => 'Line Tax',
        'total' => 'Total',
        'expected_delivery_date' => 'Expected Delivery',
        'notes' => 'Notes',
    ],

    'actions' => [
        'create' => 'Create Purchase Order',
        'edit' => 'Edit Purchase Order',
        'view' => 'View Purchase Order',
        'send_rfq' => 'Send RFQ',
        'send' => 'Send to Vendor',
        'confirm' => 'Confirm Purchase Order',
        'mark_done' => 'Mark as Done',
        'cancel' => 'Cancel Purchase Order',
        'receive_goods' => 'Receive Goods',
        'create_bill' => 'Create Vendor Bill',
        'add_line' => 'Add Line',
        'remove_line' => 'Remove Line',
    ],

    'messages' => [
        'created' => 'Purchase order created successfully.',
        'updated' => 'Purchase order updated successfully.',
        'confirmed' => 'Purchase order confirmed successfully.',
        'cancelled' => 'Purchase order cancelled successfully.',
        'cannot_edit_confirmed' => 'Cannot edit a confirmed purchase order.',
        'cannot_confirm_without_lines' => 'Cannot confirm purchase order without any lines.',
        'cannot_cancel_completed' => 'Cannot cancel a completed purchase order.',
        'fully_received' => 'All items have been received for this purchase order.',
        'partially_received' => 'Some items have been received for this purchase order.',
    ],

    'notifications' => [
        'rfq_sent' => 'RFQ sent to vendor successfully.',
        'sent' => 'Purchase order sent to vendor successfully.',
        'confirmed' => 'Purchase order confirmed successfully.',
        'marked_done' => 'Purchase order marked as done successfully.',
        'cancelled' => 'Purchase order cancelled successfully.',
    ],

    'help' => [
        'po_number' => 'Auto-generated when the purchase order is confirmed.',
        'reference' => 'External reference number or vendor quote number.',
        'exchange_rate' => 'Exchange rate used for currency conversion when the PO was created.',
        'delivery_location' => 'Default location where goods will be received.',
        'terms_and_conditions' => 'Terms and conditions for this purchase order.',
    ],

    'billing_status' => [
        'not_billed' => 'Not Billed',
        'billed' => 'Billed',
        'multiple_bills' => ':count Bills',
    ],
];
