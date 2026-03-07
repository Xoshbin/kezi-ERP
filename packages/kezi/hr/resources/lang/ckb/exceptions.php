<?php

return [
    'leave_request' => [
        'only_pending_can_be_approved' => 'تەنها ئەو داواکارییانەی مۆڵەت کە لە چاوەڕوانیدان دەتوانرێت پەسەند بکرێن.',
        'only_pending_can_be_rejected' => 'تەنها ئەو داواکارییانەی مۆڵەت کە لە چاوەڕوانیدان دەتوانرێت ڕەت بکرێنەوە.',
        'only_pending_approved_can_be_cancelled' => 'تەنها ئەو داواکارییانەی مۆڵەت کە لە چاوەڕوانیدان یان پەسەندکراون دەتوانرێت هەڵبوەشێنرێنەوە.',
        'insufficient_balance' => 'باڵانسی مۆڵەت بەس نییە. باڵانسی بەردەست: :available ڕۆژ.',
        'minimum_notice_required' => 'پێویستە لایەنی کەم پێش :days ڕۆژ ئاگاداری بدەیت.',
        'maximum_consecutive_days_exceeded' => 'زۆرترین ڕۆژی لەسەریەک ڕێگەپێدراو: :days ڕۆژ.',
        'overlapping_request' => 'داواکاری مۆڵەتەکە لەگەڵ مۆڵەتێکی تری هەبوو تێکەڵ دەبێت.',
        'refresh_failed_after_creation' => 'نوێکردنەوەی داواکاری مۆڵەت شکستیهێنا دوای دروستکردنی.',
    ],
    'position' => [
        'salary_currency_not_found' => 'دراوی مووچەی پۆستەکە نەدۆزرایەوە.',
        'max_salary_not_found' => 'بەرزترین مووچەی پۆستەکە نەدۆزرایەوە.',
        'from' => 'لە :amount',
        'up_to' => 'تاوەکو :amount',
    ],
    'casts' => [
        'salary_currency_resolution_failed' => 'نەتوانرا دراوی مووچە بۆ مۆدێلی :model دیاری بکرێت. تکایە دڵنیاببە لەوەی مۆدێلەکە currency_id یان پەیوەندی کۆمپانیای هەبێت.',
        'payroll_currency_resolution_failed' => 'نەتوانرا دراوی پێرۆڵ بۆ مۆدێلی :model دیاری بکرێت. تکایە دڵنیاببە لەوەی مۆدێلەکە پەیوەندی پێرۆڵی هەبێت.',
        'collection_empty' => 'کۆکراوەی دراوی مووچە خاڵییە.',
    ],
    'payroll' => [
        'active_contract_required' => 'کارمەندەکە گرێبەستێکی چالاکی نییە.',
        'only_draft_can_be_approved' => 'تەنها ئەو پێرۆڵە (لیستی مووچە)یانەی لە ڕەشنووسدان دەتوانرێت پەسەند بکرێن.',
        'already_paid' => 'مووچەی ئەم پێرۆڵە پێشتر دراوە.',
        'only_processed_can_be_paid' => 'تەنها ئەو پێرۆڵانەی کە پڕۆسێس کراون دەتوانرێت پارەکەیان بدرێت.',
        'no_bank_journal_found' => 'هیچ جۆرناڵێکی بانکی بۆ ئەم کۆمپانیایە نەدۆزرایەوە. <a href=":link" class="underline font-bold">لە ڕێکخستنەکانی کۆمپانیا ڕێکی بخە</a>.',
        'salary_payable_account_not_configured' => 'هیچ هەژمارێکی مووچەی وەرگیراو (Salary Payable) بۆ ئەم کۆمپانیایە ڕانەگیراوە. <a href=":link" class="underline font-bold">لە ڕێکخستنەکانی کۆمپانیا ڕێکی بخە</a>.',
        'refresh_failed_after_creation' => 'نوێکردنەوەی پێرۆڵ شکستیهێنا دوای دروستکردنی.',
    ],
    'attendance' => [
        'already_clocked_in' => 'کارمەندەکە پێشتر ئەمڕۆ کاتی دەستپێکردنی کارەکەی تۆمار کردووە (Clock In).',
        'not_clocked_in' => 'کارمەندەکە ئەمڕۆ کاتی دەستپێکردنی کارەکەی تۆمار نەکردووە.',
        'already_clocked_out' => 'کارمەندەکە پێشتر ئەمڕۆ کاتی کۆتاییهاتنی کارەکەی تۆمار کردووە (Clock Out).',
        'refresh_failed_after_clock_out' => 'نوێکردنەوەی زانیارییەکانی ئامادەبوون شکستیهێنا دوای تۆمارکردنی کاتی کۆتاییهاتن.',
        'break_already_started' => 'پشووەکە پێشتر دەستی پێکراوە.',
        'refresh_failed_after_break_start' => 'نوێکردنەوەی زانیارییەکانی ئامادەبوون شکستیهێنا دوای دەستپێکردنی پشوو.',
        'break_not_started' => 'پشووەکە دەستی پێنەکراوە.',
        'break_already_ended' => 'پشووەکە پێشتر کۆتایی پێهێنراوە.',
        'refresh_failed_after_break_end' => 'نوێکردنەوەی زانیارییەکانی ئامادەبوون شکستیهێنا دوای کۆتاییهێنان بە پشوو.',
    ],
    'employee' => [
        'refresh_failed_after_creation' => 'نوێکردنەوەی زانیارییەکانی کارمەند شکستیهێنا دوای دروستکردنی.',
        'only_terminated_can_be_reactivated' => 'تەنها ئەو کارمەندانەی کە دەرکراون/کارەکەیان کۆتاییهاتووە دەتوانرێت کارا بکرێنەوە.',
    ],
    'cash_advance' => [
        'only_draft_can_be_submitted' => 'تەنها ئەو پێشینە نەختینەییانەی لە ڕەشنووسدان دەتوانرێت بنێردرێن.',
        'only_pending_settlement_can_be_settled' => 'تەنها ئەو پێشینە نەختینەییانەی چاوەڕوانی یەکلاکردنەوەن دەتوانرێت یەکلا بکرێنەوە.',
        'receivable_account_not_configured' => 'هەژماری وەرگیراوی پێشینەی کارمەند بۆ کۆمپانیا ڕێک نەخراوە. <a href=":link" class="underline font-bold">لە ڕێکخستنەکانی کۆمپانیا ڕێکی بخە</a>.',
        'no_journal_found' => 'هیچ جۆرناڵێک بۆ کۆمپانیا نەدۆزرایەوە.',
        'bank_account_required_for_return' => 'هەژماری بانکی پێویستە بۆ گەڕاندنەوەی نەختینە.',
        'bank_account_required_for_reimbursement' => 'هەژماری بانکی پێویستە بۆ قەرەبووکردنەوە (Reimbursement).',
    ],
    'contract' => [
        'refresh_failed_after_creation' => 'نوێکردنەوەی گرێبەست شکستیهێنا دوای دروستکردنی.',
        'currency_not_found' => 'دراو نەدۆزرایەوە.',
        'company_not_found' => 'کۆمپانیا نەدۆزرایەوە.',
    ],
    'common' => [
        'user_not_authenticated' => 'بۆ ئەنجامدانی ئەم کارە دەبێت چوونەژوورەوەت (Login) کردبێت.',
        'field_name_required' => 'ناوی کێڵگەکە پێویستە.',
    ],
];
