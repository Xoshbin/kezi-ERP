<?php

return [
    // PaymentResource.php
    'navigation_group' => 'ژمێریاری',
    'navigation_label' => 'پارەدانەکان',
    'model_label' => 'پارەدان',
    'model_plural_label' => 'پارەدانەکان',

    'form.company_id' => 'کۆمپانیا',
    'form.journal_id' => 'ڕۆژنامچە',
    'form.currency_id' => 'دراو',
    'form.payment_date' => 'بەرواری پارەدان',
    'form.reference' => 'سەرچاوە',
    'form.amount' => 'کۆی گشتی',
    'form.payment_type' => 'جۆری پارەدان',
    'form.status' => 'دۆخ',
    'form.document_links' => 'بەڵگەنامە بەستەرکراوەکان',
    'form.document_type' => 'جۆری بەڵگەنامە',
    'form.document_type.invoice' => 'پسوڵە',
    'form.document_type.vendor_bill' => 'پسوڵەی فرۆشیار',
    'form.document_id' => 'بەڵگەنامە',
    'form.amount_applied' => 'بڕی جێبەجێکراو',

    'table.company.name' => 'کۆمپانیا',
    'table.journal.name' => 'ڕۆژنامچە',
    'table.currency.name' => 'دراو',
    'table.partner.name' => 'هاوبەش',
    'table.payment_date' => 'بەرواری پارەدان',
    'table.amount' => 'بڕ',
    'table.payment_type' => 'جۆری پارەدان',
    'table.status' => 'دۆخ',
    'table.created_at' => 'کاتی دروستبوون',
    'table.updated_at' => 'کاتی نوێکردنەوە',

    'action.confirm.notification.success' => 'پارەدان بە سەرکەوتوویی پشتڕاستکرایەوە',
    'action.confirm.notification.error' => 'هەڵە لە پشتڕاستکردنەوەی پارەدان',
    'action.confirm.label' => 'پشتڕاستکردنەوە',
    'action.confirm.requires_confirmation' => 'دڵنیایت لە پشتڕاستکردنەوەی ئەم پارەدانە؟',

    // EditPayment.php
    'edit.action.confirm.label' => 'پشتڕاستکردنەوەی پارەدان',

    // InvoicesRelationManager.php
    'relation_manager.invoices.title' => 'پسوڵەکان',
    'relation_manager.invoices.column.invoice_number' => 'ژمارەی پسوڵە',
    'relation_manager.invoices.column.invoice_date' => 'بەرواری پسوڵە',
    'relation_manager.invoices.column.due_date' => 'بەرواری شایستە',
    'relation_manager.invoices.column.status' => 'دۆخ',
    'relation_manager.invoices.column.total_amount' => 'کۆی گشتی',
    'relation_manager.invoices.column.amount_applied' => 'بڕی جێبەجێکراو',
    'relation_manager.invoices.form.invoice_number' => 'ژمارەی پسوڵە',
    'relation_manager.invoices.form.invoice_date' => 'بەرواری پسوڵە',
    'relation_manager.invoices.form.due_date' => 'بەرواری شایستە',
    'relation_manager.invoices.form.status' => 'دۆخ',
    'relation_manager.invoices.form.total_amount' => 'کۆی گشتی',

    // VendorBillsRelationManager.php
    'relation_manager.vendor_bills.title' => 'پسوڵەکانی فرۆشیار',
    'relation_manager.vendor_bills.column.bill_reference' => 'سەرچاوەی پسوڵە',
    'relation_manager.vendor_bills.column.bill_date' => 'بەرواری پسوڵە',
    'relation_manager.vendor_bills.column.due_date' => 'بەرواری شایستە',
    'relation_manager.vendor_bills.column.status' => 'دۆخ',
    'relation_manager.vendor_bills.column.total_amount' => 'کۆی گشتی',
    'relation_manager.vendor_bills.column.amount_applied' => 'بڕی جێبەجێکراو',
    'relation_manager.vendor_bills.form.bill_reference' => 'سەرچاوەی پسوڵە',
    'relation_manager.vendor_bills.form.bill_date' => 'بەرواری پسوڵە',
    'relation_manager.vendor_bills.form.accounting_date' => 'بەرواری ژمێریاری',
    'relation_manager.vendor_bills.form.due_date' => 'بەرواری شایستە',
    'relation_manager.vendor_bills.form.status' => 'دۆخ',
    'relation_manager.vendor_bills.form.total_amount' => 'کۆی گشتی',
];