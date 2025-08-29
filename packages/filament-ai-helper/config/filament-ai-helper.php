<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Configuration
    |--------------------------------------------------------------------------
    |
    | General package settings
    |
    */
    'enabled' => env('AI_HELPER_ENABLED', true),
    'auto_register' => env('AI_HELPER_AUTO_REGISTER', true),

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
        'button_label' => env('AI_HELPER_BUTTON_LABEL', 'AI Assistant'),
        'button_icon' => env('AI_HELPER_BUTTON_ICON', 'heroicon-o-sparkles'),
        'brand_name' => env('AI_HELPER_BRAND_NAME', 'AI Assistant'),
        'modal_width' => env('AI_HELPER_MODAL_WIDTH', 'lg'),
        'modal_max_height' => env('AI_HELPER_MODAL_MAX_HEIGHT', '80vh'),
        'enable_welcome_message' => env('AI_HELPER_ENABLE_WELCOME', true),
        'position' => env('AI_HELPER_POSITION', 'bottom-right'), // bottom-right, bottom-left, top-right, top-left
        'theme' => env('AI_HELPER_THEME', 'auto'), // auto, light, dark
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
        'system_prompt' => env('AI_HELPER_SYSTEM_PROMPT', 'You are an expert AI assistant specialized in business and financial analysis. Provide clear, actionable insights and identify potential issues. Always maintain professional standards and accuracy in your responses.'),

        'context_prompts' => [
            'journal_entry' => 'Analyze the journal lines comprehensively. Is the entry balanced (debits equal credits)? Based on the line descriptions, are the accounts chosen appropriate? Are there any potential compliance issues? Review the source document if available and assess the business impact.',
            'invoice' => 'Provide a comprehensive analysis of this invoice including: 1) Customer payment history and risk assessment, 2) Gross profit margin calculation if cost data is available, 3) Payment terms appropriateness based on customer profile, 4) Revenue recognition implications, 5) Any red flags or recommendations for improvement. Include insights about the customer relationship and transaction patterns.',
            'vendor_bill' => 'Review the vendor bill comprehensively including: 1) Accuracy of amounts and tax calculations, 2) Vendor payment history and relationship analysis, 3) Impact on cash flow and accounts payable, 4) Expense categorization appropriateness, 5) Any potential cost optimization opportunities.',
            'partner' => 'Analyze this partner comprehensively including: 1) Transaction history and patterns, 2) Payment behavior and credit risk assessment, 3) Lifetime value and profitability analysis, 4) Relationship health indicators, 5) Recommendations for account management.',
            'payment' => 'Analyze this payment including: 1) Payment method appropriateness, 2) Impact on cash flow, 3) Allocation accuracy to invoices/bills, 4) Bank reconciliation implications, 5) Any unusual patterns or red flags.',
            'default' => 'Provide a comprehensive analysis of this record including its impact on the company\'s Profit & Loss and Balance Sheet, relationship context, historical patterns, risk indicators, and actionable recommendations for business improvement.',
        ],

        'max_context_length' => 8000,
        'include_relationships' => true,

        // Context mapping for automatic model detection from URLs
        'context_mapping' => [
            'invoices' => [
                'model' => env('INVOICE_MODEL', 'App\\Models\\Invoice'),
                'resource' => env('INVOICE_RESOURCE', 'App\\Filament\\Resources\\InvoiceResource'),
            ],
            'vendor-bills' => [
                'model' => env('VENDOR_BILL_MODEL', 'App\\Models\\VendorBill'),
                'resource' => env('VENDOR_BILL_RESOURCE', 'App\\Filament\\Resources\\VendorBillResource'),
            ],
            'partners' => [
                'model' => env('PARTNER_MODEL', 'App\\Models\\Partner'),
                'resource' => env('PARTNER_RESOURCE', 'App\\Filament\\Resources\\PartnerResource'),
            ],
            'journal-entries' => [
                'model' => env('JOURNAL_ENTRY_MODEL', 'App\\Models\\JournalEntry'),
                'resource' => env('JOURNAL_ENTRY_RESOURCE', 'App\\Filament\\Resources\\JournalEntryResource'),
            ],
            'payments' => [
                'model' => env('PAYMENT_MODEL', 'App\\Models\\Payment'),
                'resource' => env('PAYMENT_RESOURCE', 'App\\Filament\\Resources\\PaymentResource'),
            ],
        ],
        'eager_load_relationships' => [
            // Invoice relationships
            'customer',
            'currency',
            'invoiceLines.product',
            'invoiceLines.incomeAccount',
            'invoiceLines.tax',
            'journalEntry.lines.account',
            'payments.bankAccount',
            'paymentDocumentLinks.payment',
            'adjustmentDocuments',
            'fiscalPosition',

            // Vendor Bill relationships
            'vendor',
            'billLines.product',
            'billLines.expenseAccount',

            // Journal Entry relationships
            'journalLines.account',
            'journal',
            'createdBy',

            // Payment relationships
            'bankAccount',
            'paidToFromPartner',
            'invoices',
            'vendorBills',

            // Partner relationships
            'invoices.payments',
            'vendorBills.payments',
            'receivableAccount',
            'payableAccount',
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
