<?php

return [
    'title' => 'Dashboard',
    'financial_dashboard' => 'Financial Dashboard',
    'no_company' => 'No Company',
    'welcome_message' => 'Welcome to :company - :date',

    'financial' => [
        // Stats Overview
        'current_month_profit' => 'Current Month Profit',
        'ytd_profit' => 'Year-to-Date Profit',
        'total_receivables' => 'Total Receivables',
        'total_payables' => 'Total Payables',
        'cash_balance' => 'Cash Balance',
        'gross_margin' => 'Gross Margin',

        // Descriptions
        'profit_after_expenses' => 'Profit after all expenses',
        'year_to_date_performance' => 'Year-to-date performance',
        'outstanding_customer_invoices' => 'Outstanding customer invoices',
        'outstanding_vendor_bills' => 'Outstanding vendor bills',
        'total_cash_all_accounts' => 'Total cash in all accounts',
        'profitability_ratio' => 'Profitability ratio',

        // Chart
        'income_vs_expense_chart' => 'Income vs. Expense Trend (Last 12 Months)',
        'total_revenue' => 'Total Revenue',
        'total_expenses' => 'Total Expenses',
        'net_income' => 'Net Income',
        'month' => 'Month',
        'amount' => 'Amount',
        'no_data' => 'No Data Available',

        // Error states
        'error' => 'Error',
        'data_unavailable' => 'Financial data unavailable',
        'please_check_setup' => 'Please check your accounting setup',
    ],

    'cash_flow' => [
        'overdue_receivables' => 'Overdue Receivables',
        'overdue_payables' => 'Overdue Payables',
        'forecast_near_term' => 'Near-term Cash Forecast',
        'forecast_30_days' => '30-Day Cash Forecast',

        // Descriptions
        'immediate_collection_needed' => 'Immediate collection needed',
        'immediate_payment_needed' => 'Immediate payment needed',
        'net_cash_flow_soon' => 'Net cash flow (current + 30 days)',
        'net_cash_flow_month' => 'Net cash flow (current + 60 days)',

        // Error states
        'error' => 'Error',
        'data_unavailable' => 'Cash flow data unavailable',
        'please_check_setup' => 'Please check your accounting setup',
    ],

    'accounts' => [
        'total_accounts' => 'Total Accounts',
        'asset_accounts' => 'Asset Accounts',
        'liability_accounts' => 'Liability Accounts',
        'income_accounts' => 'Income Accounts',
        'expense_accounts' => 'Expense Accounts',

        // Descriptions
        'chart_of_accounts' => 'Chart of accounts',
        'assets_and_cash' => 'Assets and cash',
        'debts_and_obligations' => 'Debts and obligations',
        'revenue_sources' => 'Revenue sources',
        'cost_categories' => 'Cost categories',
    ],

    'exchange_rates' => [
        'no_rates' => 'No Exchange Rates',
        'no_recent' => 'No recent exchange rates available',
        'update_description' => 'Update exchange rates to see current data',
    ],
];
