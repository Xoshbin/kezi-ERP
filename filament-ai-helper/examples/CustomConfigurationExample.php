<?php

// Example: config/filament-ai-helper.php with custom configuration

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent'),
        'timeout' => env('GEMINI_API_TIMEOUT', 45), // Increased timeout for complex queries
        'max_retries' => env('GEMINI_API_MAX_RETRIES', 5), // More retries for reliability
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'button_label' => 'Financial AI',
        'button_icon' => 'heroicon-o-calculator',
        'modal_width' => '2xl', // Larger modal for better readability
        'modal_max_height' => '90vh',
        'enable_welcome_message' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Assistant Configuration - Customized for Accounting
    |--------------------------------------------------------------------------
    */
    'assistant' => [
        'system_prompt' => 'You are a senior financial analyst and CPA with expertise in GAAP, IFRS, and Iraqi accounting standards. You provide detailed, accurate financial analysis while always respecting the immutability of posted transactions. Your responses should be professional, actionable, and include specific recommendations.',
        
        'context_prompts' => [
            // Detailed prompts for different record types
            'journal_entry' => 'Perform a comprehensive analysis of this journal entry: 1) Verify debits equal credits, 2) Check account classifications per chart of accounts, 3) Assess compliance with accounting standards, 4) Identify any potential errors or improvements, 5) Explain the business impact of this transaction.',
            
            'invoice' => 'Analyze this invoice comprehensively: 1) Calculate gross profit margin and compare to industry standards, 2) Assess customer payment history and credit risk, 3) Review pricing strategy and competitiveness, 4) Check for compliance with tax regulations, 5) Recommend collection strategies if needed.',
            
            'vendor_bill' => 'Review this vendor bill thoroughly: 1) Verify mathematical accuracy of calculations, 2) Check for duplicate payments in the system, 3) Assess vendor relationship and payment terms, 4) Analyze impact on cash flow and working capital, 5) Recommend optimal payment timing.',
            
            'customer' => 'Provide customer relationship analysis: 1) Evaluate payment history and credit worthiness, 2) Calculate customer lifetime value, 3) Assess profitability of the relationship, 4) Identify upselling opportunities, 5) Recommend credit limit adjustments.',
            
            'product' => 'Analyze product performance: 1) Review pricing strategy and margins, 2) Assess inventory turnover and carrying costs, 3) Compare performance to similar products, 4) Identify seasonal trends, 5) Recommend pricing or inventory adjustments.',
            
            'account' => 'Examine account activity: 1) Review transaction patterns and trends, 2) Identify unusual or suspicious activities, 3) Assess account balance reasonableness, 4) Check compliance with account purpose, 5) Recommend account management improvements.',
            
            'default' => 'Provide comprehensive financial analysis: 1) Explain the record\'s impact on financial statements, 2) Identify potential risks or opportunities, 3) Assess compliance with accounting standards, 4) Recommend specific actions or improvements, 5) Explain business implications in simple terms.',
        ],

        'max_context_length' => 12000, // Increased for more detailed context
        'include_relationships' => true,
        'eager_load_relationships' => [
            // Comprehensive relationship loading
            'customer',
            'vendor',
            'supplier',
            'invoiceLines.product',
            'invoiceLines.account',
            'billLines.product',
            'billLines.account',
            'journalLines.account',
            'journalLines.partner',
            'payments.paymentMethod',
            'taxes.taxRate',
            'currency',
            'company',
            'createdBy',
            'approvedBy',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enhanced Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 15, // Slightly higher for power users
            'per_minutes' => 2,   // Over 2 minutes instead of 1
        ],
        'sanitize_input' => true,
        'log_requests' => env('FILAMENT_AI_HELPER_LOG_REQUESTS', true), // Enable logging by default
        'allowed_roles' => ['admin', 'accountant', 'financial_analyst'], // Role-based access
        'blocked_fields' => ['password', 'api_token', 'remember_token', 'ssn', 'tax_id'], // Additional sensitive fields
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('FILAMENT_AI_HELPER_CACHE_ENABLED', true),
        'ttl' => env('FILAMENT_AI_HELPER_CACHE_TTL', 7200), // 2 hours cache
        'key_prefix' => 'ai_helper_v2',
        'tags' => ['ai-responses', 'filament'], // Cache tags for better management
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    */
    'localization' => [
        'supported_locales' => ['en', 'ar', 'ku'], // English, Arabic, Kurdish
        'fallback_locale' => 'en',
        'auto_detect_locale' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'conversation_history' => true, // Remember conversation context
        'export_conversations' => true, // Allow exporting chat history
        'feedback_collection' => true,  // Collect user feedback on responses
        'analytics' => true,            // Track usage analytics
    ],
];

// Example: Environment-specific configuration
// .env.production
/*
GEMINI_API_KEY=your_production_api_key
GEMINI_API_TIMEOUT=60
FILAMENT_AI_HELPER_CACHE_ENABLED=true
FILAMENT_AI_HELPER_CACHE_TTL=14400
FILAMENT_AI_HELPER_LOG_REQUESTS=false
*/

// .env.development
/*
GEMINI_API_KEY=your_development_api_key
GEMINI_API_TIMEOUT=30
FILAMENT_AI_HELPER_CACHE_ENABLED=false
FILAMENT_AI_HELPER_CACHE_TTL=300
FILAMENT_AI_HELPER_LOG_REQUESTS=true
*/
