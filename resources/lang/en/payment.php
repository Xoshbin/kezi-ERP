<?php

return [
    // PaymentResource.php
    'navigation_group' => 'Accounting',
    'navigation_label' => 'Payments',
    'model_label' => 'Payment',
    'model_plural_label' => 'Payments',

    'form.company_id' => 'Company',
    'form.journal_id' => 'Journal',
    'form.currency_id' => 'Currency',
    'form.payment_date' => 'Payment Date',
    'form.reference' => 'Reference',
    'form.amount' => 'Total Amount',
    'form.payment_type' => 'Payment Type',
    'form.status' => 'Status',
    'form.document_links' => 'Document Links',
    'form.document_type' => 'Document Type',
    'form.document_type.invoice' => 'Invoice',
    'form.document_type.vendor_bill' => 'Vendor Bill',
    'form.document_id' => 'Document',
    'form.amount_applied' => 'Amount Applied',

    'table.company.name' => 'Company',
    'table.journal.name' => 'Journal',
    'table.currency.name' => 'Currency',
    'table.partner.name' => 'Partner',
    'table.payment_date' => 'Payment Date',
    'table.amount' => 'Amount',
    'table.payment_type' => 'Payment Type',
    'table.status' => 'Status',
    'table.created_at' => 'Created At',
    'table.updated_at' => 'Updated At',

    'action.confirm.notification.success' => 'Payment confirmed successfully',
    'action.confirm.notification.error' => 'Error confirming payment',
    'action.confirm.label' => 'Confirm',
    'action.confirm.requires_confirmation' => 'Are you sure you want to confirm this payment?',

    // EditPayment.php
    'edit.action.confirm.label' => 'Confirm Payment',

    // InvoicesRelationManager.php
    'relation_manager.invoices.title' => 'Invoices',
    'relation_manager.invoices.column.invoice_number' => 'Invoice Number',
    'relation_manager.invoices.column.invoice_date' => 'Invoice Date',
    'relation_manager.invoices.column.due_date' => 'Due Date',
    'relation_manager.invoices.column.status' => 'Status',
    'relation_manager.invoices.column.total_amount' => 'Total Amount',
    'relation_manager.invoices.column.amount_applied' => 'Amount Applied',
    'relation_manager.invoices.form.invoice_number' => 'Invoice Number',
    'relation_manager.invoices.form.invoice_date' => 'Invoice Date',
    'relation_manager.invoices.form.due_date' => 'Due Date',
    'relation_manager.invoices.form.status' => 'Status',
    'relation_manager.invoices.form.total_amount' => 'Total Amount',


    // VendorBillsRelationManager.php
    'relation_manager.vendor_bills.title' => 'Vendor Bills',
    'relation_manager.vendor_bills.column.bill_reference' => 'Bill Reference',
    'relation_manager.vendor_bills.column.bill_date' => 'Bill Date',
    'relation_manager.vendor_bills.column.due_date' => 'Due Date',
    'relation_manager.vendor_bills.column.status' => 'Status',
    'relation_manager.vendor_bills.column.total_amount' => 'Total Amount',
    'relation_manager.vendor_bills.column.amount_applied' => 'Amount Applied',
    'relation_manager.vendor_bills.form.bill_reference' => 'Bill Reference',
    'relation_manager.vendor_bills.form.bill_date' => 'Bill Date',
    'relation_manager.vendor_bills.form.accounting_date' => 'Accounting Date',
    'relation_manager.vendor_bills.form.due_date' => 'Due Date',
    'relation_manager.vendor_bills.form.status' => 'Status',
    'relation_manager.vendor_bills.form.total_amount' => 'Total Amount',
];