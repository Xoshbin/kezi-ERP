<?php

return [
    'common' => [
        'user_not_authenticated' => 'بەکارهێنەر دەبێت بچێتە ژوورەوە بۆ ئەنجامدانی ئەم کردارە.',
        'journal_entry_not_found' => 'تۆماری ڕۆژنامە نەدۆزرایەوە.',
        'invalid_record_type' => 'جۆری تۆمار نادروستە.',
        'company_not_found' => 'کۆمپانیا نەدۆزرایەوە.',
        'currency_not_found' => 'دراو نەدۆزرایەوە.',
        'company_base_currency_not_found' => 'دراوی بنەڕەتی کۆمپانیا نەدۆزرایەوە.',
        'currency_id_not_found' => 'دراو بە ناسنامەی :id نەدۆزرایەوە.',
        'default_accounts_payable_missing' => 'هەژماری قەرزەکانی پێشوەختە دیارینەکراوە بۆ ئەم کۆمپانیایە.',
        'default_tax_account_missing' => 'هەژماری باجی بنەڕەتی دیارینەکراوە بۆ ئەم کۆمپانیایە.',
        'default_purchase_journal_missing' => 'ڕۆژنامەی کڕینی بنەڕەتی کۆمپانیا دیارینەکراوە.',
        'product_missing_for_line' => 'بەرهەم بوونی نییە بۆ دێڕی :id.',
        'journal_default_debit_account_missing' => 'هەژماری قەرزاری بنەڕەتی ڕۆژنامە دیارینەکراوە.',
        'default_accounts_receivable_missing' => 'هەژماری وەرگرتنی بنەڕەتی دیارینەکراوە بۆ ئەم کۆمپانیایە.',
        'default_accounts_receivable_or_sales_journal_missing' => 'هەژماری وەرگرتنی بنەڕەتی یان ڕۆژنامەی فرۆشتن دیارینەکراوە بۆ ئەم کۆمپانیایە.',
        'tax_account_missing_for_tax' => 'هەژماری باج دیارینەکراوە بۆ باجی :tax وە هەژماری باجی داخڵبووی کۆمپانیا دیارینەکراوە.',
        'journal_currency_missing' => 'دراوی ڕۆژنامە دیارینەکراوە.',
        'default_payroll_journal_missing' => 'ڕۆژنامەی مووچەی بنەڕەتی دیارینەکراوە بۆ ئەم کۆمپانیایە.',
    ],
    'lock_date' => [
        'period_locked' => 'ماوەکە قفڵ کراوە تا بەرواری :date.',
        'cannot_modify_hard_lock' => 'ناتوانرێت بەرواری قفڵی ڕەق دەستکاری بکرێت.',
        'cannot_remove_hard_lock' => 'ناتوانرێت بەرواری قفڵی ڕەق بسڕدرێتەوە.',
        'company_required' => 'کۆمپانیا پێویستە بۆ پشتڕاستکردنەوەی بەرواری قفڵ.',
    ],
    'balance_sheet' => [
        'not_balanced' => 'سەرمایەکان (:assets) یەکسان نین بە ئیلتزامات و مافی خاوەندارێتی (:liabilities_equity).',
    ],
    'budget' => [
        'exceeded' => 'مامەڵەکە لە بودجەی بەردەست بۆ :budget تێپەڕ دەبێت (هەژمار: :account). بەردەست: :available، داواکراو: :requested',
    ],
    'journal_entry' => [
        'unbalanced' => 'ناتوانرێت تۆمارێکی نایەکسان قبوڵ بکرێت.',
        'deletion_not_allowed_posted' => 'ناتوانرێت تۆماری ڕۆژنامەی نێردراو بسڕدرێتەوە. ڕاستکردنەوەکان دەبێت بە تۆمارێکی پێچەوانەی نوێ بکرێن.',
        'only_posted_can_be_reversed' => 'تەنها تۆمارە نێردراوەکانی ڕۆژنامە دەکرێت پێچەوانە بکرێنەوە.',
        'cannot_modify_posted' => 'ناتوانرێت دەستکاری قەیدی بڵاوکراوە بکرێت.',
        'failed_to_refresh_after_creation' => 'نوێکردنەوەی قەیدی ڕۆژنامە دوای دروستکردن سەرکەوتوو نەبوو.',
    ],
    'journal' => [
        'deletion_not_allowed_entries' => 'ناتوانرێت دەفتەرێکی ڕۆژنامە کە تۆماری ڕۆژنامەی پەیوەندیداری هەبێت بسڕدرێتەوە.',
    ],
    'bank_reconciliation' => [
        'no_items_selected' => 'هیچ بڕگەیەک هەڵنەبژێردراوە بۆ هاوتاکردن.',
        'missing_config' => 'کۆمپانیای ":company" ڕێکخستنی هەژماری بانکی بنەڕەتی یان هەژمارە هەڵپەسێردراوەکانی نییە. تکایە <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">لێرەدا ڕێکی بخە</a>.',
        'no_bank_lines' => 'هیچ هێڵێکی بانکی دابین نەکراوە بۆ هاوتاکردن.',
        'totals_mismatch' => 'کۆی هێڵەکانی کەشف حسابی بانک لەگەڵ کۆی پارەدانەکان یەکسان نییە.',
    ],
    'asset' => [
        'deletion_not_allowed_confirmed' => 'ناتوانرێت سەرمایەی پەسەندکراو بسڕدرێتەوە. تەنها سەرمایە ڕەشنووسەکان دەکرێت بسڕدرێنەوە.',
        'deletion_not_allowed_depreciation' => 'ناتوانرێت سەرمایەیەک کە تۆماری داخورانی هەبێت بسڕدرێتەوە. مێژووی داخوران دەبێت بپارێزرێت.',
        'deletion_not_allowed_journal' => 'ناتوانرێت سەرمایەیەک کە تۆماری ڕۆژنامەی پەیوەندیداری هەبێت بسڕدرێتەوە. تۆمارە داراییەکان دەبێت بپارێزرێن.',
        'default_bank_account_missing' => 'هەژماری بانکی بنەڕەتی کۆمپانیا ڕێکنەخراوە.',
        'default_bank_journal_missing' => 'دەفتەری ڕۆژنامەی بانکی بنەڕەتی کۆمپانیا ڕێکنەخراوە.',
        'posted_depreciation_cannot_be_updated' => 'ناتوانرێت تۆماری داخورانی پەسەندکراو نوێبکرێتەوە.',
        'posted_depreciation_cannot_be_deleted' => 'ناتوانرێت تۆماری داخورانی پەسەندکراو بسڕدرێتەوە.',
        'failed_to_refresh_depreciation_entry' => 'نوێکردنەوەی قەیدی دابەزینی نرخ دوای گۆڕانکاری سەرکەوتوو نەبوو.',
    ],
    'account' => [
        'deletion_not_allowed_financial_records' => 'ناتوانرێت هەژمارێک کە تۆماری دارایی پەیوەندیداری هەبێت بسڕدرێتەوە.',
    ],
    'tax_report' => [
        'generator_not_found' => 'پۆلی دروستکەری ڕاپۆرتی باج :class نەدۆزرایەوە.',
        'generator_invalid_contract' => 'پۆلی :class دەبێت گرێبەستی TaxReportGeneratorContract جێبەجێ بکات.',
    ],
    'consolidation' => [
        'invoice_company_not_found' => 'کۆمپانیای وەسڵەکە نەدۆزرایەوە.',
        'reciprocal_vendor_not_found' => 'هاوبەشی فرۆشیاری بەرامبەر نەدۆزرایەوە لە کۆمپانیای :target_company کە بەستراوەتەوە بە :source_company. تکایە سەرەتا ئەم هاوبەشە بە دەستی دروست بکە.',
        'average_rate_period_required' => 'بەرواری دەستپێک و کۆتایی ماوەکە پێویستن بۆ وەرگێڕانی تێکڕای نرخ.',
        'average_rate_not_calculable' => 'هیچ تێکڕای نرخێک نادۆزرێتەوە بۆ :source بۆ :target',
        'unsupported_translation_method' => 'ڕێگای وەرگێڕانی پشتگیری نەکراو: :method',
    ],
    'fiscal_year' => [
        'no_previous_year_found' => 'هیچ ساڵێکی دارایی پێشوو نەدۆزرایەوە بۆ کۆمپانیای ":company".',
        'cannot_generate_opening_entry' => 'ناتوانرێت قەیدی کرانەوە دروست بکرێت: ساڵی پێشوو کراوە/هاوسەنگ نییە، وە هیچ هەژمارێکی سەرمایە نەدۆزرایەوە بۆ دانانی قازانجی پوختتە.',
        'no_miscellaneous_journal_found' => 'هیچ ڕۆژنامەیەکی جۆراوجۆر نەدۆزرایەوە بۆ قەیدی کرانەوە.',
    ],
    'revaluation' => [
        'cannot_be_posted' => 'ناتوانرێت ئەم هەڵسەنگاندنەوەیە بنێردرێت.',
    ],
    'loan' => [
        'company_not_found' => 'کۆمپانیای قەرز نەدۆزرایەوە.',
        'currency_not_found' => 'دراوی قەرز نەدۆزرایەوە.',
        'currency_missing' => 'دراوی قەرز دیارینەکراوە.',
    ],
    'partner_ledger' => [
        'missing_accounts' => 'هاوبەشی ":partner" هەژماری وەرگرتن یان پارەدانی بۆ دیاری نەکراوە.',
    ],
    'exchange_gain_loss' => [
        'account_id_required' => 'هەژماری قازانج/زیانی دەستکەوتوو پێویستە.',
        'bank_journal_required' => 'کۆمپانیای ":company" دەفتەری ڕۆژنامەی بانکی بنەڕەتی نییە. تکایە <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">لێرەدا ڕێکی بخە</a>.',
    ],
    'inventory_bill' => [
        'only_storable_items' => 'ئەم کردارە تەنها بۆ وەسڵەکان بەکاردێت کە کاڵای کۆگاکراویان تێدایە.',
        'product_missing_inventory_account' => 'بەرهەم بە ناسنامەی :id هەژماری کۆگای بنەڕەتی نییە.',
    ],
    'expense_bill' => [
        'invalid_asset_category' => 'جۆری سەرمایەی نادروست هەڵبژێردراوە لە دێڕی وەسڵەکە.',
    ],
    'vendor_bill' => [
        'invalid_asset_category' => 'جۆری سەرمایەی نادروست لە دێڕی وەسڵەکە.',
        'product_missing_stock_input_account' => 'بەرهەم بە ناسنامەی :id هەژماری داخڵبووی کۆگای نییە.',
    ],
    'cheque' => [
        'invalid_context' => 'دەقی نادروست: :context',
        'handover_only_payable' => 'پێدان تەنها بۆ چەکی پارەدانە.',
        'deposit_only_receivable' => 'دانان تەنها بۆ چەکی وەرگرتنە.',
        'default_pdc_payable_missing' => 'هەژماری چەکی دواخراوی پێدان دیارینەکراوە.',
        'default_pdc_receivable_missing' => 'هەژماری چەکی دواخراوی وەرگرتن دیارینەکراوە.',
    ],
    'payment' => [
        'withholding_tax_account_missing' => 'هەژماری باجی ڕاگیراو دیارینەکراوە بۆ جۆری: :type',
        'standalone_withholding_needs_partner' => 'پارەدانی سەربەخۆ ناتوانێت قەیدی باجی ڕاگیراوی هەبێت بەبێ هاوبەش.',
        'standalone_needs_counterpart_account' => 'پارەدانی سەربەخۆ بەبێ هاوبەش پێویستە هەژماری بەرامبەری هەبێت.',
    ],
    'withholding_tax' => [
        'at_least_one_entry_required' => 'پێویستە لانی کەم یەک قەیدی باجی ڕاگیراو هەڵبژێردرێت بۆ بڕوانامەکە.',
        'entries_certified_or_invalid_vendor' => 'هەندێک لە قەیدەکان پێشتر بڕوانامەیان بۆ دەرچووە یان سەر بەم فرۆشیارە نین.',
        'entries_must_have_same_currency' => 'پێویستە هەموو قەیدەکان هەمان دراویان هەبێت.',
    ],
    'reconciliation' => [
        'default_bank_or_outstanding_receipts_missing' => 'هەژماری بانک یان وەسڵی ماوەی بنەڕەتی دیارینەکراوە بۆ ئەم کۆمپانیایە.',
    ],
    'payroll' => [
        'payroll_line_has_no_amount' => 'دێڕی مووچەی :id هیچ بڕێکی نییە.',
    ],
];
