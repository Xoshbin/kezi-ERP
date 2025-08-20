<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Google Gemini API integration.
    |
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent'),
        'timeout' => env('GEMINI_API_TIMEOUT', 30),
        'max_retries' => env('GEMINI_API_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the user interface elements.
    |
    */
    'ui' => [
        'button_label' => 'AccounTech Pro',
        'button_icon' => 'heroicon-o-sparkles',
        'modal_width' => 'lg',
        'modal_max_height' => '80vh',
        'enable_welcome_message' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Assistant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI assistant behavior and prompts.
    |
    */
    'assistant' => [
        'system_prompt' => 'You are AccounTech Pro, an expert accounting assistant for an Iraqi company adhering to GAAP/IFRS principles. Your advice must always respect the law of immutability for posted records. Provide clear, actionable insights and identify potential issues.',
        
        'context_prompts' => [
            'journal_entry' => 'Analyze the journal lines. Is the entry balanced (debits equal credits)? Based on the line descriptions, are the accounts chosen appropriate? Are there any potential compliance issues?',
            'invoice' => 'Calculate the gross profit margin for this invoice. Analyze the customer payment history (if provided) and assess the payment risk. Are the payment terms appropriate?',
            'vendor_bill' => 'Review the vendor bill for accuracy. Check if the amounts and tax calculations are correct. Assess the impact on cash flow and accounts payable.',
            'default' => 'Provide a simple explanation of this record\'s impact on the company\'s Profit & Loss and Balance Sheet. Identify any potential issues or recommendations.',
        ],

        'max_context_length' => 8000,
        'include_relationships' => true,
        'eager_load_relationships' => [
            'customer',
            'vendor', 
            'invoiceLines.product',
            'billLines.product',
            'journalLines.account',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the AI assistant.
    |
    */
    'security' => [
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 10,
            'per_minutes' => 1,
        ],
        'sanitize_input' => true,
        'log_requests' => env('FILAMENT_AI_HELPER_LOG_REQUESTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching AI responses.
    |
    */
    'cache' => [
        'enabled' => env('FILAMENT_AI_HELPER_CACHE_ENABLED', true),
        'ttl' => env('FILAMENT_AI_HELPER_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => 'filament_ai_helper',
    ],
];
