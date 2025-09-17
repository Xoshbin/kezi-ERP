<?php

return [
    'label' => 'پێناسەی خانەی تایبەت',
    'plural_label' => 'پێناسەکانی خانەی تایبەت',
    'navigation_label' => 'خانە تایبەتەکان',
    'section_title' => 'خانە تایبەتەکان',

    'fields' => [
        'model_type' => 'جۆری مۆدێل',
        'name' => 'ناو',
        'description' => 'وەسف',
        'is_active' => 'چالاک',
        'field_definitions' => 'پێناسەکانی خانە',
        'field_key' => 'کلیلی خانە',
        'field_label' => 'ناونیشانی خانە',
        'field_type' => 'جۆری خانە',
        'field_required' => 'پێویست',
        'field_options' => 'هەڵبژاردنەکان',
        'field_validation_rules' => 'یاساکانی پشتڕاستکردنەوە',
        'field_help_text' => 'دەقی یارمەتی',
        'field_order' => 'ڕیز',
        'option_value' => 'نرخ',
        'option_label' => 'ناونیشان',
    ],

    'sections' => [
        'basic_information' => 'زانیاری بنەڕەتی',
        'basic_information_description' => 'ڕێکخستنی بنەڕەتی بۆ ئەم پێناسەی خانە تایبەتە.',
        'field_definitions' => 'پێناسەکانی خانە',
        'field_definitions_description' => 'خانە تایبەتەکان پێناسە بکە کە بۆ ئەم مۆدێلە بەردەست دەبن.',
        'field_configuration' => 'ڕێکخستنی خانە',
        'field_options' => 'هەڵبژاردنەکانی خانە',
        'field_validation' => 'پشتڕاستکردنەوە و یارمەتی',
    ],

    'actions' => [
        'add_field' => 'خانە زیاد بکە',
        'remove_field' => 'خانە لابە',
        'add_option' => 'هەڵبژاردن زیاد بکە',
        'remove_option' => 'هەڵبژاردن لابە',
        'move_up' => 'بۆ سەرەوە',
        'move_down' => 'بۆ خوارەوە',
    ],

    'placeholders' => [
        'field_key' => 'نموونە: پەیوەندی_فریاکەوتن',
        'field_label' => 'نموونە: پەیوەندی فریاکەوتن',
        'field_help_text' => 'زانیاری زیاتر بۆ یارمەتیدانی بەکارهێنەران',
        'validation_rules' => 'نموونە: max:255, email',
        'option_value' => 'نموونە: هەڵبژاردن١',
        'option_label' => 'نموونە: هەڵبژاردنی ١',
    ],

    'help' => [
        'model_type' => 'جۆری مۆدێل هەڵبژێرە کە ئەم خانە تایبەتانە بەکاردەهێنێت.',
        'field_key' => 'ناسنامەیەکی تایبەت بۆ ئەم خانەیە. تەنها پیتی بچووک، ژمارە و هێڵی ژێرەوە بەکاربهێنە.',
        'field_type' => 'جۆری خانەی تێخستن کە بۆ بەکارهێنەران پیشان دەدرێت.',
        'field_required' => 'ئایا ئەم خانەیە دەبێت لەلایەن بەکارهێنەرانەوە پڕ بکرێتەوە.',
        'field_options' => 'بۆ خانەکانی هەڵبژاردن، هەڵبژاردنە بەردەستەکان پێناسە بکە.',
        'validation_rules' => 'یاساکانی زیاتری پشتڕاستکردنەوەی Laravel (بە کۆما جیاکراوە).',
        'field_order' => 'ڕیزبەندی کە ئەم خانەیە لە فۆڕمەکاندا دەردەکەوێت.',
    ],

    'validation' => [
        'field_key_required' => 'کلیلی خانە پێویستە.',
        'field_key_unique' => 'کلیلی خانە دەبێت لەناو ئەم پێناسەیەدا تایبەت بێت.',
        'field_key_format' => 'کلیلی خانە دەبێت تەنها پیتی بچووک، ژمارە و هێڵی ژێرەوە لەخۆبگرێت.',
        'field_label_required' => 'ناونیشانی خانە پێویستە.',
        'field_type_required' => 'جۆری خانە پێویستە.',
        'select_options_required' => 'خانەکانی هەڵبژاردن دەبێت لانیکەم یەک هەڵبژاردنیان هەبێت.',
        'option_value_required' => 'نرخی هەڵبژاردن پێویستە.',
        'option_label_required' => 'ناونیشانی هەڵبژاردن پێویستە.',
    ],

    'messages' => [
        'no_fields_defined' => 'هێشتا هیچ خانەیەکی تایبەت بۆ ئەم مۆدێلە پێناسە نەکراوە.',
        'definition_saved' => 'پێناسەی خانەی تایبەت بە سەرکەوتوویی پاشەکەوت کرا.',
        'definition_deleted' => 'پێناسەی خانەی تایبەت بە سەرکەوتوویی سڕایەوە.',
        'field_added' => 'خانە بە سەرکەوتوویی زیادکرا.',
        'field_removed' => 'خانە بە سەرکەوتوویی لابرا.',
        'invalid_model_type' => 'جۆری مۆدێلی نادروست هەڵبژێردراوە.',
    ],

    'model_types' => [
        'App\\Models\\Partner' => 'هاوبەشەکان',
        'App\\Models\\Product' => 'بەرهەمەکان',
        'App\\Models\\Employee' => 'کارمەندەکان',
        'App\\Models\\Department' => 'بەشەکان',
        'App\\Models\\Position' => 'پۆستەکان',
        'App\\Models\\Asset' => 'سامانەکان',
        'App\\Models\\Project' => 'پڕۆژەکان',
    ],
];
