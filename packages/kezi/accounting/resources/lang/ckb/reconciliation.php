<?php

return [
    // Reconciliation Types
    'type' => [
        'manual_ar_ap' => 'دەستی بۆ قەرزەکان',
        'manual_ar_ap_description' => 'هاوتاکردنی دەستی بۆ هەژمارەکانی قەرز و کڕیارەکان',
        'bank_statement' => 'کەشف حیسابی بانک',
        'bank_statement_description' => 'هاوتاکردنی کەشف حیسابی بانک لەگەڵ پارەدانەکان',
        'manual_general' => 'دەستی گشتی',
        'manual_general_description' => 'هاوتاکردنی دەستی گشتی بۆ تۆمارەکانی ڕۆژانە',
    ],

    // Company Settings
    'company' => [
        'enable_reconciliation' => 'چالاککردنی هاوتاکردن',
        'enable_reconciliation_help' => 'چالاککردنی تایبەتمەندی هاوتاکردن بۆ ئەم کۆمپانیایە. کاتێک ناچالاکە، هەموو تایبەتمەندییەکانی هاوتاکردن دەشاردرێنەوە.',
    ],

    // Account Settings
    'account' => [
        'allow_reconciliation' => 'ڕێگەدان بە هاوتاکردن',
        'allow_reconciliation_help' => 'ڕێگەدان بە بەکارهێنانی ئەم هەژمارە لە پرۆسەکانی هاوتاکردن (قەرزەکان، بانک).',
    ],

    // Partner Unreconciled Entries
    'partner' => [
        'unreconciled_entries_relation_manager' => [
            'title' => 'تۆمارە هاوتانەکراوەکان',
            'entry_date' => 'ڕێکەوتی تۆمار',
            'reference' => 'ژمارەی بەڵگە',
            'account_code' => 'کۆدی هەژمار',
            'account_name' => 'ناوی هەژمار',
            'description' => 'وەسف',
            'debit' => 'قەرز',
            'credit' => 'بڕ',
            'reconcile_selected' => 'هاوتاکردنی دیاریکراوەکان',
            'reconcile' => 'هاوتاکردن',
            'reconcile_modal_heading' => 'هاوتاکردنی تۆمارەکانی ڕۆژانە',
            'reconcile_modal_description' => 'ئەمە تۆمارێکی هاوتاکردن دروست دەکات کە هێڵەکانی تۆماری ڕۆژانەی دیاریکراو دەبەستێتەوە. دڵنیابە لەوەی کۆی قەرز یەکسانە بە کۆی بڕ.',
            'reconcile_reference' => 'ژمارەی بەڵگە',
            'reconcile_description' => 'وەسف',
            'empty_state_heading' => 'هیچ تۆمارێکی هاوتانەکراو نییە',
            'empty_state_description' => 'هەموو هێڵەکانی تۆماری ڕۆژانە بۆ ئەم هاوبەشە هاوتاکراون یان هیچ تۆمارێک نییە لە هەژمارە هاوتاکراوەکان.',
            'use_bulk_action' => 'تکایە کرداری بە کۆمەڵ بەکاربهێنە بۆ هاوتاکردنی تۆمارەکان.',
            'reconciliation_success' => 'هاوتاکردن سەرکەوتوو بوو',
            'reconciliation_success_body' => ':count هێڵی تۆماری ڕۆژانە بە سەرکەوتوویی هاوتاکران. ژمارەی بەڵگەی هاوتاکردن: :reference',
            'reconciliation_error' => 'هەڵە لە هاوتاکردن',
            'reconciliation_error_generic' => 'هەڵەیەکی چاوەڕواننەکراو ڕوویدا لە کاتی هاوتاکردن. تکایە دووبارە هەوڵبدەرەوە.',
        ],
    ],

    // Error Messages
    'errors' => [
        'reconciliation_disabled' => 'تایبەتمەندی هاوتاکردن بۆ ئەم کۆمپانیایە ناچالاکە.',
        'account_not_reconcilable' => 'یەکێک یان زیاتر لە هەژمارەکان ڕێگە بە هاوتاکردن نادەن.',
        'unbalanced_reconciliation' => 'تۆمارە دیاریکراوەکان هاوسەنگ نین. کۆی قەرز دەبێت یەکسان بێت بە کۆی بڕ.',
        'partner_mismatch' => 'هەموو تۆمارەکان دەبێت هی یەک هاوبەش بن بۆ هاوتاکردنی قەرزەکان.',
        'already_reconciled' => 'یەکێک یان زیاتر لە تۆمارەکان پێشتر هاوتاکراون.',
        'invalid_entries' => 'هێڵی تۆماری ڕۆژانەی نادروست پێشکەشکراوە.',
        'unposted_entries' => 'ناکرێت تۆماری ڕۆژانەی نێنێردراو هاوتا بکرێت.',
    ],

    // Success Messages
    'success' => [
        'reconciliation_created' => 'هاوتاکردن بە سەرکەوتوویی دروستکرا.',
        'reconciliation_completed' => 'هاوتاکردن بە سەرکەوتوویی تەواو بوو.',
    ],

    // General
    'reconciliation' => 'هاوتاکردن',
    'reconciliations' => 'هاوتاکردنەکان',
    'reconciled' => 'هاوتاکراو',
    'unreconciled' => 'هاوتانەکراو',
    'reconciled_at' => 'هاوتاکراوە لە',
    'reconciled_by' => 'هاوتاکراوە لەلایەن',
    'reference' => 'ژمارەی بەڵگە',
    'description' => 'وەسف',
    'total_debits' => 'کۆی قەرز',
    'total_credits' => 'کۆی بڕ',
    'balance' => 'باڵانس',
    'line_count' => 'ژمارەی هێڵ',
];
