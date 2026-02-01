<?php

return [
    'label' => 'ئاستی داواکاری قەرز',
    'plural_label' => 'ئاستەکانی داواکاری قەرز',
    'fields' => [
        'name' => 'ناو',
        'days_overdue' => 'ڕۆژانی دواکەوتن',
        'send_email' => 'ناردنی ئیمەیڵ',
        'print_letter' => 'چاپکردنی نامە',
        'charge_fee' => 'هەژمارکردنی سزای دواکەوتن',
        'fee_product' => 'بەرهەمی سزا',
        'fee_amount' => 'بڕی سزای جێگیر',
        'fee_percentage' => 'ڕێژەی سزا',
        'email_subject' => 'بابەتی ئیمەیڵ',
        'email_body' => 'ناوەڕۆکی ئیمەیڵ',
    ],
    'sections' => [
        'general_information' => 'زانیاری گشتی',
        'late_fee_configuration' => 'ڕێکخستنی سزای دواکەوتن',
        'email_configuration' => 'ڕێکخستنی ئیمەیڵ',
    ],
    'helpers' => [
        'days_overdue' => 'ژمارەی ڕۆژەکان دوای بەرواری کۆتایی بۆ کاراکردنی ئەم ئاستە',
        'email_configuration' => 'قالبئ ئیمەیڵەکە بە دەستی دیاری بکە.',
    ],
];
