<?php

return [
    'currency' => [
        'provider_not_registered' => "Provider ':identifier' is not registered",
        'not_found' => 'Currency :currency not found',
        'no_exchange_rate' => 'No exchange rate found for :currency on :date',
        'in_use' => 'Cannot delete a currency that is in use.',
    ],
    'partner' => [
        'in_use' => 'Cannot delete a partner that is in use.',
        'company_or_currency_not_found' => 'Partner company or currency not found',
    ],
    'cast' => [
        'invalid_money_value' => 'Invalid value for MoneyCast: must be numeric or Money instance.',
        'empty_original_currency' => 'Original currency collection is empty',
        'empty_foreign_currency' => 'Foreign currency collection is empty',
        'empty_currency' => 'Currency collection is empty',
        'missing_internal_currency' => 'Model does not have an original_currency_id or foreign_currency_id for OriginalCurrencyMoneyCast.',
        'resolve_base_currency' => 'Could not resolve base currency for model :class. Please ensure the model has a valid company relationship.',
        'resolve_document_currency' => 'Could not resolve document currency for model :class. Please ensure the model has a valid parent document relationship.',
        'invoice_currency_not_found' => 'Invoice currency not found',
        'vendor_bill_currency_not_found' => 'Vendor bill currency not found',
        'adjustment_document_currency_not_found' => 'Adjustment document currency not found',
        'payment_currency_not_found' => 'Payment currency not found',
        'bank_statement_currency_not_found' => 'Bank statement currency not found',
        'loan_currency_not_found' => 'Loan currency not found',
        'purchase_order_currency_not_found' => 'Purchase order currency not found',
        'sales_order_currency_not_found' => 'Sales order currency not found',
        'quote_currency_not_found' => 'Quote currency not found',
        'installmentable_currency_not_found' => 'Installmentable currency not found',
    ],
];
