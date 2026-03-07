<?php

return [
    'common' => [
        'user_not_authenticated' => 'بەکارهێنەر دەبێت بچێتە ژوورەوە بۆ ئەنجامدانی ئەم کردارە.',
        'journal_entry_not_found' => 'تۆماری ڕۆژنامە نەدۆزرایەوە.',
        'invalid_record_type' => 'جۆری تۆمار نادروستە.',
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
    ],
    'revaluation' => [
        'cannot_be_posted' => 'ناتوانرێت ئەم هەڵسەنگاندنەوەیە بنێردرێت.',
    ],
    'loan' => [
        'company_not_found' => 'کۆمپانیای قەرز نەدۆزرایەوە.',
        'currency_not_found' => 'دراوی قەرز نەدۆزرایەوە.',
    ],
    'partner_ledger' => [
        'missing_accounts' => 'هاوبەشی ":partner" هەژماری وەرگرتن یان پارەدانی بۆ دیاری نەکراوە.',
    ],
    'exchange_gain_loss' => [
        'account_id_required' => 'هەژماری قازانج/زیانی دەستکەوتوو پێویستە.',
        'bank_journal_required' => 'کۆمپانیای ":company" دەفتەری ڕۆژنامەی بانکی بنەڕەتی نییە. تکایە <a href=":url" class="underline font-medium text-danger-600 dark:text-danger-400">لێرەدا ڕێکی بخە</a>.',
    ],
];
