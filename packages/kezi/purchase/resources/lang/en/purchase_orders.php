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
        'vendor_currency_info' => 'Vendor & Currency Information',
        'vendor_currency_info_description' => 'Select vendor and currency details for this order',
        'order_details' => 'Order Details',
        'order_details_description' => 'Basic information about the purchase order',
        'basic_information' => 'Basic Information',
        'vendor_currency_information' => 'Vendor & Currency Information',
        'delivery_info' => 'Delivery Information',
        'delivery_information' => 'Delivery Information',
        'line_items' => 'Line Items',
        'line_items_description' => 'Add products and services to this purchase order',
        'notes' => 'Notes & Terms',
        'totals' => 'Totals',
        'lines' => 'Order Lines',
        'attachments' => 'Attachments',
        'attachments_description' => 'Manage document attachments for this order',
    ],

    'fields' => [
        'id' => 'ID',
        'po_number' => 'PO Number',
        'status' => 'Status',
        'po_date' => 'PO Date',
        'reference' => 'Reference',
        'vendor' => 'Vendor',
        'currency' => 'Currency',
        'expected_delivery_date' => 'Expected Delivery Date',
        'incoterm' => 'Incoterm',
        'incoterm_location' => 'Incoterm Location',
        'delivery_location' => 'Delivery Location',
        'notes' => 'Notes',
        'terms_and_conditions' => 'Terms & Conditions',
        'exchange_rate' => 'Exchange Rate',
        'total_amount' => 'Total Amount',
        'total_tax' => 'Total Tax',
        'created_by' => 'Created By',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'created_by_user' => 'Created By',
        'confirmed_at' => 'Confirmed At',
        'cancelled_at' => 'Cancelled At',
        'exchange_rate_at_creation' => 'Exchange Rate',
        'total_amount_company_currency' => 'Total (Company Currency)',
        'total_tax_company_currency' => 'Tax (Company Currency)',
        'billing_status' => 'Billing Status',
        // Line item fields
        'lines' => 'Line Items',
        'product' => 'Product',
        'description' => 'Description',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'tax' => 'Tax',
        'shipping_type' => 'Shipping Type',
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

    'billing_status' => [
        'not_billed' => 'Not Billed',
        'billed' => 'Billed',
        'multiple_bills' => ':count Bills',
    ],

    'actions' => [
        'create' => 'Create Purchase Order',
        'edit' => 'Edit Purchase Order',
        'view' => 'View Purchase Order',
        'send_rfq' => 'Send RFQ',
        'send' => 'Send to Vendor',
        'confirm' => 'Confirm',
        'ready_to_receive' => 'Ready to Receive',
        'ready_to_receive_confirmation_title' => 'Ready to Receive Goods',
        'ready_to_receive_confirmation_description' => 'This will mark the purchase order as ready to receive goods from the vendor.',
        'mark_done' => 'Mark as Done',
        'cancel' => 'Cancel',
        'receive_goods' => 'Receive Goods',
        'create_bill' => 'Create Vendor Bill',
        'create_bill_confirmation_title' => 'Create Vendor Bill',
        'create_bill_confirmation_description' => 'This will automatically create a vendor bill with all line items from this purchase order. The bill will be created in Draft status for your review.',
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
        'confirmed' => 'Purchase order confirmed successfully',
        'confirm_failed' => 'Confirming purchase order failed',
        'cancelled' => 'Purchase order cancelled successfully',
        'rfq_sent' => 'RFQ sent to vendor successfully.',
        'sent' => 'Purchase order sent to vendor successfully.',
        'ready_to_receive' => 'Purchase order is now ready to receive goods.',
        'marked_done' => 'Purchase order marked as done successfully.',
        'bill_created_successfully' => 'Vendor Bill Created Successfully',
        'bill_created_body' => 'Vendor bill :reference has been created and is ready for review.',
        'bill_creation_failed' => 'Failed to Create Vendor Bill',
        'update_not_allowed' => 'Update Not Allowed',
    ],

    'help' => [
        'po_number' => 'Auto-generated when saved',
        'reference' => 'Your internal reference or vendor quote number',
        'terms_and_conditions' => 'Standard terms and conditions for this purchase order',
        'exchange_rate' => 'Exchange rate used for currency conversion when the PO was created.',
        'delivery_location' => 'Default location where goods will be received.',
        'status_can_create_bill' => 'Vendor bills can be created from this purchase order.',
        'status_cannot_create_bill' => 'Change status to Confirmed, To Receive, or later to enable bill creation.',
        'status_bills_already_exist' => 'Vendor bills already exist for this purchase order.',
        'status_forward_only' => 'Status can only be changed forward in the workflow.',
    ],
];
