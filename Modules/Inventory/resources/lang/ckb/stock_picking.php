<?php

return [
    'label' => 'جوڵەی کۆگا',
    'plural_label' => 'جوڵەکانی کۆگا',
    'navigation_label' => 'جوڵەکانی کۆگا',

    // GRN specific
    'goods_receipt' => 'وەسڵەنامەی وەرگرتنی کاڵا',
    'goods_receipt_plural' => 'وەسڵەنامەکانی وەرگرتنی کاڵا',
    'grn_number' => 'ژمارەی وەسڵەنامەی وەرگرتن',
    'validated_at' => 'بەرواری پەسەندکردن',
    'validated_by' => 'پەسەندکراوە لەلایەن',

    // Types
    'types' => [
        'receipt' => 'وەرگرتن',
        'delivery' => 'گەیاندن',
        'internal' => 'ناوخۆیی',
    ],

    // States
    'states' => [
        'draft' => 'ڕەشنووس',
        'confirmed' => 'پەسەندکراو',
        'assigned' => 'دیاریکراو',
        'done' => 'تەواو',
        'cancelled' => 'هەڵوەشاوە',
    ],

    // Form labels
    'reference' => 'سەرچاوە',
    'partner' => 'هاوبەش',
    'origin' => 'سەرچاوە',
    'purchase_order' => 'داواکاری کڕین',
    'scheduled_date' => 'بەرواری پلاندانراو',
    'completed_at' => 'بەرواری تەواوبوون',

    // Actions
    'validate' => 'پەسەندکردن',
    'validate_receipt' => 'پەسەندکردنی وەرگرتن',
    'cancel' => 'هەڵوەشاندنەوە',
    'create_backorder' => 'دروستکردنی فەرمانی پاشماوە',

    // Messages
    'receipt_validated' => 'وەسڵەنامەی وەرگرتنی کاڵا بە سەرکەوتوویی پەسەندکرا.',
    'cannot_validate' => 'ناتوانرێت ئەم جوڵەی کۆگایە پەسەندبکرێت.',
    'already_done' => 'ئەم جوڵەی کۆگایە پێشتر تەواوکراوە.',
    'already_cancelled' => 'ئەم جوڵەی کۆگایە پێشتر هەڵوەشاوەتەوە.',

    // Partial receipt
    'partial_receipt' => 'وەرگرتنی بەشەکی',
    'backorder_created' => 'فەرمانی پاشماوە دروستکرا بۆ بڕی ماوە.',
    'remaining_quantity' => 'بڕی ماوە',
    'quantity_to_receive' => 'بڕی بۆ وەرگرتن',
    'planned_quantity' => 'بڕی پلاندانراو',
    'received_quantity' => 'بڕی وەرگیراو',
];
