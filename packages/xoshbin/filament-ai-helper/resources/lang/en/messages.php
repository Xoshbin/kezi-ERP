<?php

return [
    'button_label' => config('filament-ai-helper.ui.brand_name', 'AI Assistant'),
    'modal_title' => config('filament-ai-helper.ui.brand_name', 'AI Assistant'),
    'modal_description' => 'Get intelligent insights and analysis for your accounting records',

    'chat' => [
        'placeholder' => 'Ask me anything about this record...',
        'send_button' => 'Send',
        'sending_button' => 'Thinking...',
        'clear_button' => 'Clear',
        'close_button' => 'Close',
        'keyboard_shortcut' => 'Press Ctrl+Enter to send',
        'empty_state' => 'Start a conversation with your AI assistant',
    ],

    'record_info' => [
        'analyzing' => 'Analyzing: :type :identifier',
        'unknown_type' => 'Unknown',
        'not_available' => 'N/A',
    ],

    'errors' => [
        'api_error' => 'Sorry, I encountered an error while processing your request. Please try again.',
        'rate_limit' => 'Too many requests. Please wait a moment before sending another message.',
        'no_api_key' => 'AI Assistant is not configured. Please contact your administrator.',
        'invalid_record' => 'Unable to load record information.',
    ],

    'validation' => [
        'question_required' => 'Please enter a question.',
        'question_min' => 'Your question must be at least :min characters long.',
        'question_max' => 'Your question cannot exceed :max characters.',
    ],

    'welcome_messages' => [
        'default' => 'Hello! I\'m AccounTech Pro, your AI accounting assistant. How can I help you today?',
        'with_record' => 'Hello! I can see you\'re looking at :model :identifier. I\'m AccounTech Pro, your AI accounting assistant. I can help you analyze this record, check for potential issues, and provide insights based on accounting best practices. What would you like to know?',
        'no_record' => 'Hello! I\'m AccounTech Pro, your AI accounting assistant. I can help you with accounting questions and analysis. How can I assist you today?',
    ],

    'fallback_response' => 'I apologize, but I\'m currently unable to analyze this :model record due to a technical issue. Please try again later or contact support if the problem persists.',
];
