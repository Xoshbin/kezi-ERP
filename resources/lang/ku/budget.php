<?php

return [
    // BudgetResource
    'form' => [
        'company_id' => 'کۆمپانیا',
        'name' => 'ناو',
        'period_start_date' => 'ڕێکەوتی دەستپێکی ماوە',
        'period_end_date' => 'ڕێکەوتی کۆتایی ماوە',
        'budget_type' => 'جۆری بودجە',
        'status' => 'دۆخ',
        'default_status' => 'ڕەشنووس',
    ],
    'table' => [
        'company_name' => 'ناوی کۆمپانیا',
        'name' => 'ناو',
        'period_start_date' => 'ڕێکەوتی دەستپێکی ماوە',
        'period_end_date' => 'ڕێکەوتی کۆتایی ماوە',
        'budget_type' => 'جۆری بودجە',
        'status' => 'دۆخ',
        'created_at' => 'کاتی دروستبوون',
        'updated_at' => 'کاتی نوێکردنەوە',
    ],

    // BudgetLinesRelationManager
    'budget_lines' => [
        'label' => 'هێڵی بودجە',
        'plural_label' => 'هێڵەکانی بودجە',
        'form' => [
            'analytic_account_id' => 'هەژماری شیکاری',
            'account_id' => 'هەژمار',
            'budgeted_amount' => 'بڕی بودجە',
            'achieved_amount' => 'بڕی بەدەستهاتوو',
            'committed_amount' => 'بڕی پابەندبوو',
        ],
        'table' => [
            'analytic_account_name' => 'ناوی هەژماری شیکاری',
            'account_name' => 'ناوی هەژمار',
            'budgeted_amount' => 'بڕی بودجە',
            'achieved_amount' => 'بڕی بەدەستهاتوو',
            'committed_amount' => 'بڕی پابەندبوو',
        ],
    ],
];