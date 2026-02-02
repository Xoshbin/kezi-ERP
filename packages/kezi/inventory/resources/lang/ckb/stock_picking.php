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

    // View Stock Picking
    'moves' => 'جوڵەکان',
    'total_moves' => 'کۆی جوڵەکان',
    'move_details' => 'وردەکارییەکانی جوڵە',
    'stock_moves' => 'جوڵەکانی کۆگا',
    'product' => 'بەرهەم',
    'actual_fulfilled_quantity' => 'بڕی جێبەجێکراو / ڕاستەقینە',
    'assigned_lots' => 'گروپە دیاریکراوەکان',
    'validate_done' => 'پەسەندکردن (تەواو)',
    'operations' => 'کردارەکان',

    'fields' => [
        'type' => 'جۆر',
        'state' => 'دۆخ',
    ],

    'sections' => [
        'actual_quantities' => 'بڕی ڕاستەقینە',
        'confirm_quantities_description' => 'بڕی ڕاستەقینە پشتڕاست بکەرەوە کە بۆ هەر جوڵەیەک هەڵبژێردراوە.',
    ],

    'placeholders' => [
        'no_lots_assigned' => 'هیچ بەشێک دیاری نەکراوە',
    ],

    'modal' => [
        'assign_picking' => 'دیاریکردنی هەڵبژاردن',
        'assign' => 'دیاریکردن',
        'reserve_stock_description' => 'کۆگا و بەشە تایبەتەکان بۆ ئەم هەڵبژاردنە دیاری بکە.',
        'stock_moves' => 'جوڵەی کۆگا',
        'review_moves_description' => 'پێداچوونەوە و دیاریکردنی بەشەکان بۆ هەر جوڵەیەکی کۆگا لەم هەڵبژاردنەدا.',
        'product' => 'بەرهەم',
        'from' => 'لە',
        'to' => 'بۆ',
        'lot_assignments' => 'دیاریکردنی بەشەکان',
        'lot' => 'بەش',
        'quantity' => 'بڕ',
        'add_lot' => 'زیادکردنی بەش',
    ],

    'notifications' => [
        'error' => 'هەڵە',
        'validated' => 'هەڵبژاردن پەسەندکرا',
        'assigned' => 'هەڵبژاردن دیاریکرا',
        'assigned_body' => 'هەڵبژاردنەکە بە سەرکەوتوویی دیاریکرا. کۆگا پارێزراوە و بەشەکان تەرخانکراون.',
        'failed_to_assign' => 'نەتوانرا هەڵبژاردن دیاری بکرێت: :error',
        'no_lines_to_validate' => 'هیچ هێڵێک نییە بۆ پەسەندکردن.',
    ],
];
