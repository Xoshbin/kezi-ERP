<?php

return [
    'model_label' => 'Fiscal Period',
    'plural_model_label' => 'Fiscal Periods',
    'field_name' => 'Name',
    'field_start_date' => 'Start Date',
    'field_end_date' => 'End Date',
    'field_state' => 'Status',
    'action_close' => 'Close Period',
    'action_reopen' => 'Reopen Period',
    'close_confirmation_title' => 'Close Fiscal Period?',
    'close_confirmation_desc' => 'This will lock all transactions in this period. Continue?',
    'closed_successfully' => 'Fiscal period closed successfully.',
    'close_failed' => 'Failed to close fiscal period.',
    'reopen_confirmation_title' => 'Reopen Fiscal Period?',
    'reopen_confirmation_desc' => 'This will unlock transactions in this period. Continue?',
    'reopened_successfully' => 'Fiscal period reopened successfully.',
    'reopen_failed' => 'Failed to reopen fiscal period.',
    'validation' => [
        'not_open' => 'Fiscal period is not in \'open\' state.',
        'not_closed' => 'Fiscal period is not in \'closed\' state.',
        'year_closed' => 'Cannot close period: the fiscal year is already closed.',
        'year_closed_reopen' => 'Cannot reopen period: the fiscal year is closed.',
        'draft_entries' => 'There are :count draft journal entries in this period. Please post or delete them.',
    ],
];
