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
        'system_prompt' => 'You are AccounTech Pro, a senior financial analyst and certified public accountant specializing in Iraqi business operations. You have deep expertise in GAAP, IFRS, and Iraqi accounting regulations. You provide comprehensive financial analysis while strictly respecting the immutability of posted transactions. Your responses are professional, actionable, and include specific recommendations. You understand multi-currency operations, analytic accounting, and modern ERP systems. Always consider the Iraqi business context, tax implications, and regulatory compliance in your analysis.',

        'context_prompts' => [
            // Core Financial Documents
            'journalentry' => 'Analyze this journal entry comprehensively: 1) Verify debits equal credits and entry is balanced, 2) Review account classifications and ensure proper chart of accounts usage, 3) Check analytic account assignments for cost center tracking, 4) Assess compliance with GAAP/IFRS standards, 5) Identify any potential posting errors or improvements needed.',

            'invoice' => 'Provide detailed invoice analysis: 1) Calculate gross profit margins and compare to industry benchmarks, 2) Assess customer payment history and credit risk factors, 3) Review invoice line accuracy including product pricing and tax calculations, 4) Evaluate payment terms appropriateness, 5) Identify collection strategies if overdue.',

            'vendorbill' => 'Conduct thorough vendor bill review: 1) Verify mathematical accuracy of all calculations, 2) Check proper account coding and expense categorization, 3) Validate tax calculations and compliance, 4) Assess cash flow impact and payment timing, 5) Compare against purchase orders and contracts for accuracy.',

            // Financial Management
            'payment' => 'Analyze payment transaction details: 1) Verify payment method and amount accuracy, 2) Check proper allocation to invoices/bills, 3) Review currency exchange rates if applicable, 4) Assess payment timing and cash flow impact, 5) Identify any reconciliation issues.',

            'partner' => 'Evaluate partner financial relationship: 1) Analyze payment history and credit worthiness, 2) Calculate outstanding balances and aging, 3) Assess relationship profitability and lifetime value, 4) Review credit limits and payment terms, 5) Recommend relationship management strategies.',

            'account' => 'Review account performance: 1) Analyze transaction patterns and trends, 2) Verify account balance reasonableness, 3) Check proper transaction classification, 4) Identify unusual activities requiring investigation, 5) Assess reconciliation status and requirements.',

            // Specialized Documents
            'bankstatement' => 'Analyze bank statement for reconciliation: 1) Identify unmatched transactions and discrepancies, 2) Review unusual activities or patterns, 3) Check for potential fraud indicators, 4) Assess reconciliation completeness, 5) Recommend follow-up actions.',

            'adjustmentdocument' => 'Review adjustment document: 1) Verify proper justification and authorization, 2) Check mathematical accuracy and account impacts, 3) Assess compliance with accounting standards, 4) Review supporting documentation, 5) Evaluate financial statement impact.',

            'budget' => 'Analyze budget performance: 1) Compare actual vs budgeted amounts, 2) Calculate variance percentages and trends, 3) Identify significant deviations requiring attention, 4) Assess forecast accuracy, 5) Recommend budget adjustments.',

            'asset' => 'Review asset management: 1) Verify asset valuation and depreciation calculations, 2) Check asset condition and utilization, 3) Assess impairment indicators, 4) Review maintenance and insurance status, 5) Evaluate disposal or replacement needs.',

            'tax' => 'Analyze tax implications: 1) Verify tax calculation accuracy, 2) Check compliance with local tax regulations, 3) Assess tax optimization opportunities, 4) Review filing requirements and deadlines, 5) Identify potential tax risks.',

            'currency' => 'Review currency and exchange rate impacts: 1) Analyze foreign exchange gains/losses, 2) Check rate accuracy and timing, 3) Assess hedging strategies, 4) Review multi-currency transaction impacts, 5) Evaluate currency risk exposure.',

            'default' => 'Provide comprehensive financial analysis: 1) Explain impact on financial statements (P&L, Balance Sheet, Cash Flow), 2) Identify potential risks and opportunities, 3) Assess compliance with accounting standards and regulations, 4) Recommend specific actions for improvement, 5) Explain business implications in clear terms.',
        ],

        'max_context_length' => 8000,
        'include_relationships' => true,
        'eager_load_relationships' => [
            // Core relationships
            'partner',
            'company',
            'currency',
            'journal',

            // Line item relationships (based on your actual models)
            'invoiceLines.product',
            'invoiceLines.account',
            'invoiceLines.analyticAccount',
            'vendorBillLines.product',
            'vendorBillLines.account',
            'vendorBillLines.analyticAccount',
            'journalEntryLines.account',
            'journalEntryLines.partner',
            'journalEntryLines.analyticAccount',
            'adjustmentDocumentLines.product',
            'adjustmentDocumentLines.account',

            // Financial and tax relationships
            'fiscalPosition',
            'taxes',
            'currencyRate',

            // Payment relationships
            'payments.journal',
            'payments.currency',
            'paymentDocumentLinks',

            // Bank and reconciliation
            'bankStatementLines',
            'reconciliations',

            // Analytic relationships
            'analyticAccounts',
            'analyticPlan',

            // Asset relationships
            'depreciationEntries',
            'stockMoves',
            'inventoryCostLayers',

            // Audit and user relationships
            'createdBy',
            'updatedBy',
            'auditLogs',
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
