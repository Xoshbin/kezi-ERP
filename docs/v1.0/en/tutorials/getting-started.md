---
title: Getting Started
icon: heroicon-o-rocket-launch
order: 0
---


This comprehensive guide helps you get started with your accounting system, covering initial setup, essential configuration, and basic workflows. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is This Accounting System?

This is a modern, web-based accounting application that provides comprehensive financial management capabilities for businesses of all sizes. The system follows international accounting standards and supports multi-currency operations.

**Key Features:**
- **Complete Financial Management**: Handle invoices, bills, payments, and journal entries
- **Multi-Currency Support**: Operate in multiple currencies with automatic conversion
- **Bank Reconciliation**: Match bank statements with your accounting records
- **Real-Time Dashboard**: Monitor financial health with live KPIs and charts
- **Multi-Language Interface**: Available in English, Arabic, and Kurdish
- **Role-Based Access**: Secure access controls for different user types

**Accounting Foundation**: The system implements double-entry bookkeeping principles, ensuring every transaction maintains the fundamental accounting equation: Assets = Liabilities + Equity.

---

## System Requirements

### User Permissions
- **Basic User**: Create and edit transactions, view reports
- **Accountant**: Full access to accounting features and reconciliation
- **Administrator**: Complete system access including settings and user management

---

## Initial System Setup

### Step 1: Accessing the System

Navigate to your accounting system URL and log in with your credentials.

### Step 2: Company Registration (First-Time Setup)

If no company exists, you'll be prompted to register one:

**Company Name**: Enter your business legal name

**Currency**: Select your primary operating currency (defaults to Iraqi Dinar)

**Fiscal Country**: Country code for tax regulations (e.g., 'IQ' for Iraq)

**💡 Tip**: The system comes pre-configured with Iraqi Dinar (IQD) and US Dollar (USD) currencies.

### Step 3: Understanding the Dashboard

After login, you'll see the **Financial Dashboard** with:

- **Financial Stats Overview**: Current month profit, year-to-date performance, receivables, payables, and cash balance
- **Income vs Expense Chart**: 12-month trend analysis showing revenue, expenses, and net income
- **Cash Flow Widget**: Overdue amounts and short-term cash flow forecasts

**📝 Note**: Dashboard widgets update automatically as you enter transactions.

---

## Fiscal Year and Accounting Periods Setup

**⚠️ Important**: Configure your fiscal year before entering any transactions. This is foundational for accurate financial reporting and tax compliance.

### Why Fiscal Periods Matter

Fiscal periods define your business's accounting calendar and determine:
- When financial reports are generated
- Tax reporting deadlines
- Period-end closing procedures
- Comparative analysis timeframes

### Configuring Your Fiscal Year

Navigate to **Settings → Company Profile**

**Fiscal Year Start**: Most businesses use January 1st, but you can set any date that aligns with your business cycle or local regulations.

**Common Fiscal Year Options:**
- **Calendar Year**: January 1 - December 31 (most common)
- **Government Fiscal Year**: April 1 - March 31 (common in some countries)
- **Custom Business Year**: Any 12-month period that suits your business

**📝 Note**: The system automatically creates monthly periods within your fiscal year for detailed reporting and analysis.

---

## Navigation Overview

The system is organized into **clusters** (main sections) with related features grouped together:

### Accounting Cluster
Navigate to **Accounting** to access core financial features:

**Core Accounting Group:**
- **Journal Entries**: Manual accounting entries
- **Assets**: Fixed asset management

**Sales & Purchases Group:**
- **Invoices**: Customer billing and sales
- **Vendor Bills**: Supplier invoices and purchases
- **Partners**: Customer and vendor management

**Banking & Cash Group:**
- **Bank Statements**: Bank reconciliation and cash management
- **Payments**: Payment processing and tracking

### Settings Cluster
Navigate to **Settings** for system configuration:
- **Companies**: Company profile and defaults
- **Accounts**: Chart of accounts management
- **Currencies**: Multi-currency setup
- **Taxes**: Tax rates and rules

### Additional Clusters
- **Inventory Management**: Product catalog and stock tracking
- **Human Resources**: Employee and payroll management

---

## Essential Configuration

### Currency Setup and Exchange Rates

Navigate to **Settings → Currencies**

**Default Currencies**: The system comes pre-configured with:
- **Iraqi Dinar (IQD)**: Primary currency for Iraqi businesses
- **US Dollar (USD)**: Common international currency

**Adding Additional Currencies:**
Click **Create** to add currencies for international operations:
- **Currency Code**: ISO code (e.g., EUR, GBP, SAR)
- **Currency Name**: Full name in multiple languages
- **Symbol**: Currency symbol (€, £, ﷼)
- **Decimal Places**: Usually 2, but 3 for IQD
- **Exchange Rate**: Current rate against your base currency

**💡 Best Practice**: Update exchange rates regularly for accurate multi-currency reporting.

### Tax Configuration

Navigate to **Settings → Taxes**

**Default Tax Rates**: The system includes pre-configured Iraqi tax rates:
- **VAT 10%**: Standard Value Added Tax rate for Iraq
- **Tax Exempt**: For tax-free transactions

**Customizing Tax Rates:**
- Review default rates for accuracy with current regulations
- Add additional tax rates as needed for your business
- Configure tax accounts for proper reporting

### Chart of Accounts Setup

Navigate to **Settings → Accounts**

**Default Chart of Accounts**: The system provides a comprehensive chart of accounts including:

**Asset Accounts (1000s):**
- Bank accounts (USD and IQD)
- Cash accounts (USD and IQD)
- Accounts receivable
- Inventory
- Fixed assets (equipment, vehicles, buildings)

**Liability Accounts (2000s):**
- Accounts payable
- VAT payable
- Long-term debt

**Equity Accounts (3000s):**
- Share capital
- Retained earnings
- Current year earnings

**Income Accounts (4000s):**
- Product sales
- Service revenue
- Consulting revenue

**Expense Accounts (5000s):**
- Cost of goods sold
- Salaries and wages
- Rent, utilities, depreciation

**Customizing Your Chart of Accounts:**
Click **Create** to add accounts specific to your business:
- **Code**: Unique account number following the numbering system
- **Name**: Descriptive account name (supports multiple languages)
- **Type**: Select appropriate account type
- **Currency**: Usually matches company currency

**💡 Best Practice**: The default chart covers most business needs. Only add accounts that are specific to your industry or reporting requirements.

### Default Journals Setup

Navigate to **Settings → Journals** (configured automatically)

**Default Journals**: The system creates essential journals automatically:

**Operational Journals:**
- **Sales (INV)**: For customer invoices
- **Purchases (BILL)**: For vendor bills

**Bank & Cash Journals:**
- **Bank (IQD)**: Iraqi Dinar bank transactions
- **Bank (USD)**: US Dollar bank transactions
- **Cash (IQD)**: Iraqi Dinar cash transactions
- **Cash (USD)**: US Dollar cash transactions

**Special Purpose Journals:**
- **Miscellaneous Operations (MISC)**: Manual journal entries
- **Depreciation (DEPR)**: Asset depreciation entries

**📝 Note**: These journals are automatically configured with appropriate default accounts and are ready for immediate use.

### Payment Terms Configuration

Navigate to **Settings → Payment Terms** (pre-configured)

**Default Payment Terms**: The system includes comprehensive payment terms:

**Standard Terms:**
- Immediate payment
- Net 15, Net 30, Net 45, Net 60 days

**Early Payment Discounts:**
- 2% 10, Net 30 (2% discount if paid within 10 days)
- 1% 15, Net 30 (1% discount if paid within 15 days)

**End of Month Terms:**
- EOM (End of Month)
- EOM + 15, EOM + 30

**Installment Terms:**
- 50% Now, 50% in 30 Days
- Quarterly payments

**💡 Best Practice**: Review these terms and activate only those relevant to your business practices.

### Partner (Customer/Vendor) Setup

Navigate to **Accounting → Partners**

**Step 1: Create Your First Customer**
Click **Create** and enter:
- **Name**: Customer business name
- **Type**: Select "Customer" or "Both" if they're also a vendor
- **Contact Information**: Address, phone, email
- **Payment Terms**: Select from pre-configured terms (e.g., Net 30)
- **Currency**: Customer's preferred currency

**Step 2: Create Your First Vendor**
Similar process, but select "Vendor" as the type.

**📝 Note**: Partners marked as "Both" can be used for both sales and purchase transactions.

### Product/Service Catalog

Navigate to **Inventory Management → Products**

**Step 1: Create Products/Services**
- **Name**: Product or service description
- **Type**: Product (physical goods) or Service
- **Unit Price**: Default selling price
- **Cost**: Purchase or production cost
- **Tax**: Select from pre-configured tax rates

**Step 2: Configure Inventory (for Products)**
- **Track Inventory**: Enable for physical products
- **Initial Stock**: Starting quantity on hand
- **Reorder Level**: Minimum stock alert threshold

**Default Stock Locations**: The system creates:
- **Warehouse**: Main inventory location
- **Vendors**: Virtual location for vendor stock

---

## Basic Transaction Workflows

### Creating Your First Customer Invoice

Navigate to **Accounting → Invoices**

**Quick Steps:**
1. Click **Create** and select customer
2. Add invoice lines with products/services
3. Review totals and tax calculations
4. Click **Post** to finalize

**Key Points:**
- Uses pre-configured Sales journal (INV)
- Automatically applies customer's payment terms
- Tax rates applied based on product configuration
- Posted invoices create automatic journal entries

**📖 For detailed instructions**: See [Customer Invoices Guide](customer-invoices.md)

### Recording Your First Vendor Bill

Navigate to **Accounting → Vendor Bills**

**Quick Steps:**
1. Click **Create** and select vendor
2. Enter vendor's invoice details
3. Add bill lines with products/services
4. Click **Post** to record the expense

**Key Points:**
- Uses pre-configured Purchases journal (BILL)
- Automatically applies vendor's payment terms
- Expense accounts auto-filled based on products
- Creates accounts payable entries

**📖 For detailed instructions**: See [Vendor Bills Guide](vendor-bills.md)

### Processing Payments

Navigate to **Accounting → Payments**

**Quick Steps:**
1. Select payment type (Receive/Send)
2. Choose partner and payment method
3. Enter amount and select bank/cash journal
4. Link to outstanding invoices/bills

**Key Points:**
- Uses appropriate bank or cash journal
- Automatically suggests outstanding amounts
- Handles multi-currency conversions
- Updates partner balances immediately

**📖 For detailed instructions**: See [Payments Guide](payments.md)

### Bank Reconciliation Basics

Navigate to **Accounting → Bank Statements**

**Quick Steps:**
1. Import or manually create bank statement
2. Match statement lines with accounting records
3. Identify and resolve discrepancies
4. Confirm reconciliation

**Key Points:**
- Requires bank reconciliation to be enabled in company settings
- Uses pre-configured bank journals
- Automatically suggests matching transactions
- Maintains complete audit trail

**📖 For detailed instructions**: See [Bank Reconciliation Guide](bank-reconciliation.md)

---

## Understanding Status Workflows

### Document Status Flow
**Draft → Posted → (Paid/Reconciled)**

- **Draft**: Being prepared, can be edited freely
- **Posted**: Finalized, creates accounting entries, cannot be edited
- **Paid/Reconciled**: Final status when fully processed

**⚠️ Important**: Only posted transactions affect financial reports and dashboard widgets.

---

## Multi-Currency Operations

### Default Currency Configuration
The system supports multi-currency operations with:
- **Iraqi Dinar (IQD)**: Typically the base currency
- **US Dollar (USD)**: Common secondary currency
- **Additional currencies**: Can be added as needed

### Multi-Currency Transactions
When creating transactions in foreign currencies:
- **Exchange Rate**: Captured automatically at transaction date
- **Company Currency Amounts**: Calculated automatically
- **Gain/Loss**: Recorded when payments are made at different rates

**Example**:
- Invoice: $1,000 USD at rate 1,500 IQD/USD = 1,500,000 IQD
- Payment: $1,000 USD at rate 1,520 IQD/USD = 1,520,000 IQD
- Exchange Gain: 20,000 IQD

---

## Financial Dashboard Overview

### Key Performance Indicators

**Financial Stats Overview:**
- Current month and year-to-date profit
- Outstanding receivables and payables
- Cash balance across all accounts
- Gross profit margin

**Income vs Expense Chart:**
- 12-month trend analysis
- Visual comparison of revenue and expenses
- Net income tracking over time

**Cash Flow Widget:**
- Overdue receivables requiring immediate attention
- Overdue payables needing payment
- 7-day and 30-day cash flow forecasts

### Accessing Financial Reports
Navigate to **Accounting → Reports** for comprehensive reporting:
- Profit & Loss Statement
- Balance Sheet
- Aged Receivables/Payables
- Trial Balance
- Cash Flow Statement

**📝 Note**: All reports are generated in real-time based on posted transactions.

---

## Best Practices for New Users

### System Setup Checklist

Before entering transactions, ensure you have:
- ✅ Configured fiscal year and accounting periods
- ✅ Reviewed and customized chart of accounts
- ✅ Set up currencies and exchange rates
- ✅ Configured tax rates for your jurisdiction
- ✅ Created essential partners (customers/vendors)
- ✅ Added products/services to catalog
- ✅ Verified default journals and payment terms

### Daily Workflow Recommendations

**Morning Routine:**
1. Check dashboard for key financial indicators
2. Review overdue receivables and payables
3. Process any pending payments

**Transaction Entry:**
1. Enter transactions promptly (same day when possible)
2. Always verify partner information before posting
3. Use pre-configured products and tax rates
4. Post transactions only when completely verified

**End of Day:**
1. Review draft transactions for completeness
2. Post confirmed transactions
3. Check cash balances against bank records

### Data Entry Best Practices

**Leverage Default Data:**
- Use pre-configured payment terms instead of custom terms
- Select from existing tax rates rather than manual calculations
- Choose from product catalog for consistent pricing
- Use standard chart of accounts before adding custom accounts

**Accuracy and Consistency:**
- Enter reference numbers for easy tracking
- Use meaningful transaction descriptions
- Maintain consistent naming conventions
- File supporting documents systematically

**Security:**
- Log out when finished working
- Don't share login credentials
- Report suspicious activity immediately
- Regular backup of important data

---

## Troubleshooting

### Common Issues

**Q: I can't edit a posted transaction. What should I do?**
A: Posted transactions are locked to maintain audit trails. To make changes:
- Create reversing entries for corrections
- Use adjustment documents for complex changes
- Contact your accountant for guidance on proper procedures

**Q: My dashboard shows zero balances but I've entered transactions.**
A: Check that transactions are **Posted**, not just saved as drafts. Only posted transactions affect financial reports and dashboard widgets.

**Q: Exchange rates seem incorrect on my foreign currency transactions.**
A: Verify that:
- Currency exchange rates are current in **Settings → Currencies**
- Transaction dates are correct (rates are captured at transaction date)
- Your base currency is properly configured

**Q: I can't find a specific account in the dropdown.**
A: The system provides comprehensive default accounts. Before creating new accounts:
- Check if an existing account serves the same purpose
- Verify the account type matches your needs
- Consult your accountant about chart of accounts structure

### Error Messages

**"Cannot delete a company with associated financial records"**
- This protects data integrity
- Archive the company instead of deleting
- Contact administrator if deletion is absolutely necessary

**"Transaction date is in a locked period"**
- Your accountant has locked past periods for accuracy
- Use current date or contact accountant to unlock period

### Getting Help

- Use the **Help/Docs** button in resource headers for specific guidance
- Contact your system administrator for access issues
- Consult with your accountant for complex accounting questions
- Refer to detailed user guides for comprehensive procedures

---

## Next Steps

### Immediate Actions
1. **Complete System Setup**: Follow the configuration checklist above
2. **Enter Opening Balances**: Record your starting financial position
3. **Create First Transactions**: Start with simple invoices and bills
4. **Review Reports**: Check that data appears correctly in financial reports

### Ongoing Learning
- **Master Core Workflows**: Practice creating invoices, bills, and payments
- **Explore Advanced Features**: Bank reconciliation, multi-currency operations
- **Understand Reporting**: Learn to interpret financial statements and KPIs
- **Develop Procedures**: Establish consistent processes for your team

### Additional Resources
- [Customer Invoices](customer-invoices.md) - Detailed invoice management
- [Vendor Bills](vendor-bills.md) - Complete vendor bill processing
- [Payments](payments.md) - Comprehensive payment handling
- [Bank Reconciliation](bank-reconciliation.md) - Bank statement procedures
- [Opening Balances](opening-balances.md) - Setting up starting balances

---

**🎉 You're Ready to Begin!** Your accounting system is now configured with industry-standard defaults and ready for daily operations. The pre-configured chart of accounts, tax rates, payment terms, and journals provide a solid foundation for most businesses. Start with basic transactions and gradually explore advanced features as your confidence grows.
