<?php

return [
    'invoice' => [
        'modify_non_draft' => 'Cannot modify a non-draft invoice.',
        'refresh_failed' => 'Failed to refresh invoice after creation.',
        'belongs_to_another_company' => 'Invoice does not belong to the requested company.',
        'credit_note_posted_only' => 'Credit notes can only be created for confirmed/posted invoices.',
        'template_product_not_allowed' => 'Cannot create invoice lines for template products.',
        'delete_non_draft' => 'Cannot delete a non-draft invoice.',
        'reset_non_posted' => 'Only posted invoices can be reset to draft.',
        'reset_paid' => 'Cannot reset a paid or partially paid invoice to draft. Please cancel the payments first.',
        'reset_linked_adjustments' => 'Cannot reset an invoice that has linked adjustment documents. Please cancel those documents first.',
        'reset_no_journal_entry' => 'Cannot reset an invoice without a journal entry.',
        'cancel_non_posted' => 'Only posted invoices can be cancelled.',
        'cancel_no_journal_entry' => 'Cannot cancel an invoice without a journal entry.',
    ],
    'quote' => [
        'revision_sent_rejected_only' => 'Only sent or rejected quotes can have revisions created.',
        'conversion_accepted_only' => 'Only accepted quotes that have not been converted can be converted to a sales order.',
        'send_draft_only' => 'Only draft quotes can be sent.',
        'send_no_lines' => 'Cannot send a quote without line items.',
        'accept_sent_only' => 'Only sent quotes can be accepted.',
        'accept_expired' => 'Cannot accept an expired quote. Please create a new revision.',
        'update_draft_sent_only' => 'Only draft or sent quotes can be updated.',
    ],
    'sales_order' => [
        'update_not_allowed' => 'This sales order cannot be edited in its current status.',
        'cannot_invoice_status' => 'This sales order cannot be invoiced in its current status.',
        'invoice_already_exists' => 'An invoice already exists for this sales order.',
        'expected_record' => 'Expected SalesOrder record.',
        'user_not_authenticated' => 'User must be authenticated to confirm sales order.',
    ],
    'stock_move' => [
        'invoice_line_no_product' => 'Invoice line must have a product to create stock move.',
        'delivery_for_invoice' => 'Stock delivery for invoice :number',
    ],
    'actions' => [
        'view_invoices' => 'View Invoices',
        'view_journals' => 'View Journals',
        'view_sales_orders' => 'View Sales Orders',
        'edit_quote' => 'Edit Quote',
        'edit_sales_order' => 'Edit Sales Order',
        'delivery_creation_failed' => 'Failed to create delivery',
        'update_not_allowed' => 'Update Not Allowed',
        'confirmation_failed' => 'Sales Order Confirmation Failed',
    ],
];
